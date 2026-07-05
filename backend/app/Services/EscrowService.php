<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Coupon;
use App\Models\EscrowTransaction;
use App\Models\Order;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Escrow — SPEC §4.1.
 *
 * Funds are held at payment and reach the seller ONLY when the buyer's
 * 4-digit delivery code is entered. The 12% commission is deducted on
 * release, computed on the price AFTER the seller coupon.
 */
class EscrowService
{
    public function __construct(
        private readonly WalletService $wallet,
        private readonly CouponService $coupons,
    ) {}

    public function commissionRate(): float
    {
        return (float) config('anaqatuk.commission_rate');
    }

    /**
     * Hold a store's due (after seller coupon) in escrow for an order.
     */
    public function hold(Order $order, Store $store, float $storeSubtotal, ?Coupon $sellerCoupon = null): EscrowTransaction
    {
        $base = $this->coupons->commissionBase($storeSubtotal, $sellerCoupon);
        $couponDiscount = round($storeSubtotal - $base, 2);
        $commission = round($base * $this->commissionRate(), 2);
        $net = round($base - $commission, 2);

        return $order->escrowTransactions()->create([
            'store_id' => $store->id,
            'held_amount' => $base,
            'coupon_discount' => $couponDiscount,
            'commission' => $commission,
            'net_amount' => $net,
            'status' => 'held',
        ]);
    }

    /**
     * Release escrow for an order after the delivery code matches.
     * Credits the seller's unified wallet with the net (88%). SPEC §4.1.
     *
     * @throws RuntimeException on a wrong code.
     */
    public function releaseByCode(Order $order, string $code): EscrowTransaction
    {
        $expected = $order->items()->value('delivery_code');

        if ($expected === null || $code !== $expected) {
            throw new RuntimeException('✕ الكود غير صحيح — اطلبي من العميلة الكود الظاهر في تطبيقها');
        }

        return DB::transaction(function () use ($order) {
            /** @var EscrowTransaction $tx */
            $tx = $order->escrowTransactions()->where('status', 'held')->firstOrFail();

            $tx->update(['status' => 'released', 'released_at' => now()]);

            $seller = $tx->store->user;
            $this->wallet->credit(
                $seller,
                'sale_income',
                (float) $tx->net_amount,
                "💰 دخل بيع — طلب #{$order->code} (بعد عمولة ١٢٪)",
                $order->code,
            );

            // Delivering the code confirms receipt → order completed.
            $order->update([
                'status' => OrderStatus::Completed,
                'completed_at' => now(),
                'cancellable' => false,
            ]);

            $tx->store()->increment('completed_orders');

            return $tx->fresh();
        });
    }

    /** Refund a held escrow back to the buyer's wallet (free cancellation / return). */
    public function refundToBuyer(Order $order, string $reason): void
    {
        DB::transaction(function () use ($order, $reason) {
            $tx = $order->escrowTransactions()->where('status', 'held')->first();
            if (! $tx) {
                return;
            }

            $tx->update(['status' => 'refunded', 'released_at' => now()]);

            $this->wallet->credit(
                $order->buyer,
                'refund',
                (float) ($order->total - $order->deposit_total),
                $reason." — #{$order->code}",
                $order->code,
            );
        });
    }
}
