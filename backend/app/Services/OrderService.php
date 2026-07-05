<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\ProductMode;
use App\Models\Conversation;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Checkout — turns a cart into per-seller escrow orders. SPEC §3.5 / §4.
 * All prices are recomputed server-side; nothing monetary is trusted from
 * the client. SPEC §8.
 */
class OrderService
{
    public function __construct(
        private readonly EscrowService $escrow,
        private readonly RentalService $rental,
        private readonly WalletService $wallet,
    ) {}

    /**
     * @param  array<int,array{product_id:int,mode:string,color?:string,qty?:int,addon_ids?:array<int>,packaging?:string,measurements?:array,notes?:string,occasion_date?:string}>  $items
     * @return array{parent:?Order,orders:array<int,Order>}
     */
    public function checkout(User $buyer, array $items, string $paymentMethod, ?string $couponCode = null): array
    {
        if (empty($items)) {
            throw new RuntimeException('السلة فارغة');
        }

        return DB::transaction(function () use ($buyer, $items, $paymentMethod, $couponCode) {
            // Resolve + server-price every line, grouped by store.
            $byStore = [];
            foreach ($items as $raw) {
                $line = $this->priceLine($raw);
                $byStore[$line['store_id']][] = $line;
            }

            $coupon = $couponCode ? Coupon::where('code', strtoupper($couponCode))->where('active', true)->first() : null;

            $multi = count($byStore) > 1;
            $parent = null;
            $baseCode = $this->nextOrderCode();

            if ($multi) {
                $parent = Order::create([
                    'code' => $baseCode,
                    'buyer_id' => $buyer->id,
                    'status' => OrderStatus::PaidEscrow,
                    'region_id' => $buyer->region_id,
                    'payment_method' => $paymentMethod,
                    'placed_at' => Carbon::now(),
                    'cancellable' => true,
                ]);
            }

            $orders = [];
            $suffix = 'A';
            foreach ($byStore as $storeId => $lines) {
                $store = Store::findOrFail($storeId);
                $code = $multi ? $baseCode.'-'.$suffix++ : $baseCode;

                $subtotal = array_sum(array_column($lines, 'line_total'));
                $depositTotal = array_sum(array_column($lines, 'deposit'));
                $storeCoupon = ($coupon && $coupon->owner->value === 'store' && $coupon->store_id === $store->id) ? $coupon : null;

                $order = Order::create([
                    'code' => $code,
                    'buyer_id' => $buyer->id,
                    'store_id' => $store->id,
                    'parent_order_id' => $parent?->id,
                    'status' => OrderStatus::PaidEscrow,
                    'subtotal' => round($subtotal, 2),
                    'delivery_fee' => (float) $store->delivery_flat_price,
                    'deposit_total' => round($depositTotal, 2),
                    'total' => round($subtotal + (float) $store->delivery_flat_price + $depositTotal, 2),
                    'is_custom' => collect($lines)->contains(fn ($l) => $l['mode'] === ProductMode::Custom->value),
                    'region_id' => $buyer->region_id,
                    'payment_method' => $paymentMethod,
                    'placed_at' => Carbon::now(),
                    'cancellable' => true,
                ]);

                // One delivery code per shipment (per store order). SPEC §4.1.
                $deliveryCode = $this->makeDeliveryCode();

                foreach ($lines as $l) {
                    $item = $order->items()->create([
                        'product_id' => $l['product_id'],
                        'store_id' => $store->id,
                        'title' => $l['title'],
                        'mode' => $l['mode'],
                        'color' => $l['color'] ?? null,
                        'measurements' => $l['measurements'] ?? null,
                        'notes' => $l['notes'] ?? null,
                        'addons' => $l['addons'] ?? null,
                        'packaging' => $l['packaging'] ?? null,
                        'packaging_price' => $l['packaging_price'],
                        'qty' => $l['qty'],
                        'unit_price' => $l['unit_price'],
                        'addons_total' => $l['addons_total'],
                        'deposit' => $l['deposit'],
                        'line_total' => $l['line_total'],
                        'delivery_code' => $deliveryCode,
                    ]);

                    // Rental: create the held deposit + block the calendar.
                    if ($l['mode'] === ProductMode::Rent->value) {
                        $deposit = $order->deposits()->create([
                            'buyer_id' => $buyer->id,
                            'store_id' => $store->id,
                            'amount' => $l['deposit'],
                            'status' => 'held',
                        ]);
                        if (! empty($l['occasion_date'])) {
                            $this->rental->book(Product::find($l['product_id']), $order, Carbon::parse($l['occasion_date']));
                        }
                        unset($deposit);
                    }
                }

                // Hold escrow for the store's item subtotal (after seller coupon).
                $this->escrow->hold($order, $store, $subtotal, $storeCoupon);

                // Open (or unlock a payfirst) conversation now that payment is in. SPEC §4.8.
                $this->openConversation($buyer, $store, $order);

                $orders[] = $order;
            }

            // Pay from the unified wallet when chosen.
            if ($paymentMethod === 'wallet') {
                $grand = array_sum(array_map(fn ($o) => (float) $o->total, $orders));
                $this->wallet->debit($buyer, 'payment', $grand, 'دفعتِ من المحفظة — طلب #'.$baseCode, $baseCode);
            }

            return ['parent' => $parent, 'orders' => $orders];
        });
    }

    /** Recompute a line's price from the catalog — never trust the client. */
    private function priceLine(array $raw): array
    {
        /** @var Product $product */
        $product = Product::with(['store', 'modes', 'addons', 'colors'])->findOrFail($raw['product_id']);

        if (! $product->store->status->acceptsOrders()) {
            throw new RuntimeException('هذا المتجر لا يستقبل الطلبات حاليًا');
        }

        $mode = $product->modes->firstWhere('type', ProductMode::from($raw['mode']));
        if (! $mode) {
            throw new RuntimeException('نمط بيع غير متاح لهذا المنتج');
        }

        // Colour stock (hidden from buyer) must cover the requested qty. SPEC §4.6.
        $qty = max(1, (int) ($raw['qty'] ?? 1));
        if (! empty($raw['color'])) {
            $color = $product->colors->firstWhere('name', $raw['color']);
            if ($color && $color->qty < $qty) {
                throw new RuntimeException('الكمية المطلوبة غير متوفرة لهذا اللون');
            }
        }

        $addonsTotal = 0.0;
        $addons = [];
        foreach ($raw['addon_ids'] ?? [] as $addonId) {
            $addon = $product->addons->firstWhere('id', $addonId);
            if ($addon) {
                $addonsTotal += (float) $addon->price;
                $addons[] = ['name' => $addon->name, 'price' => (float) $addon->price];
            }
        }

        // Packaging price from the store's configured packaging.
        $packagingPrice = 0.0;
        $packaging = $raw['packaging'] ?? null;
        foreach ((array) $product->store->packaging as $pack) {
            if (($pack['key'] ?? null) === $packaging && ($pack['enabled'] ?? false)) {
                $packagingPrice = (float) ($pack['price'] ?? 0);
            }
        }

        $unit = (float) $mode->price;
        $deposit = $mode->type === ProductMode::Rent ? (float) $mode->deposit : 0.0;
        $lineTotal = round($unit * $qty + $addonsTotal + $packagingPrice, 2);

        return [
            'store_id' => $product->store_id,
            'product_id' => $product->id,
            'title' => $product->title,
            'mode' => $mode->type->value,
            'color' => $raw['color'] ?? null,
            'qty' => $qty,
            'unit_price' => $unit,
            'addons' => $addons,
            'addons_total' => round($addonsTotal, 2),
            'packaging' => $packaging,
            'packaging_price' => round($packagingPrice, 2),
            'deposit' => $deposit,
            'measurements' => $raw['measurements'] ?? null,
            'notes' => $raw['notes'] ?? null,
            'occasion_date' => $raw['occasion_date'] ?? null,
            'line_total' => $lineTotal,
        ];
    }

    private function openConversation(User $buyer, Store $store, Order $order): Conversation
    {
        return Conversation::updateOrCreate(
            ['buyer_id' => $buyer->id, 'store_id' => $store->id, 'order_id' => $order->id],
            ['locked' => false], // payment is in → chat is open even for payfirst stores
        );
    }

    private function makeDeliveryCode(): string
    {
        // In non-production seed/tests the fixed dev code keeps flows reproducible.
        if (! app()->environment('production')) {
            return (string) config('anaqatuk.otp_dev_code');
        }

        return str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    }

    private function nextOrderCode(): string
    {
        $n = 1043 + Order::whereNull('parent_order_id')->count();

        return 'A-'.$n;
    }
}
