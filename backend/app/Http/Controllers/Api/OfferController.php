<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Enums\SmartRequestStatus;
use App\Http\Resources\OrderResource;
use App\Models\Conversation;
use App\Models\Order;
use App\Models\SmartRequestOffer;
use App\Services\EscrowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OfferController extends ApiController
{
    public function __construct(private readonly EscrowService $escrow) {}

    /**
     * POST /api/offers/{offer}/accept
     * The buyer accepts a seller offer → an escrow order is created from it and
     * the smart request is marked accepted.
     */
    public function accept(Request $request, SmartRequestOffer $offer)
    {
        $offer->load(['smartRequest', 'store']);
        $buyer = $request->user();

        abort_unless($offer->smartRequest->buyer_id === $buyer->id, 403);
        abort_unless($offer->smartRequest->status->isActive(), 422, 'الطلب لم يعد مفتوحًا');

        $order = DB::transaction(function () use ($offer, $buyer) {
            $store = $offer->store;
            $price = (float) $offer->price;
            $deliveryFee = (float) $store->delivery_flat_price;

            $order = Order::create([
                'code' => $this->nextOrderCode(),
                'buyer_id' => $buyer->id,
                'store_id' => $store->id,
                'smart_request_id' => $offer->smart_request_id,
                'status' => OrderStatus::PaidEscrow,
                'subtotal' => $price,
                'delivery_fee' => $deliveryFee,
                'total' => round($price + $deliveryFee, 2),
                'region_id' => $buyer->region_id,
                'payment_method' => 'wallet',
                'placed_at' => now(),
                'cancellable' => true,
            ]);

            $order->items()->create([
                'store_id' => $store->id,
                'title' => 'طلب خاص — '.$offer->smartRequest->category,
                'mode' => 'custom',
                'qty' => 1,
                'unit_price' => $price,
                'line_total' => $price,
                'delivery_code' => $this->deliveryCode(),
            ]);

            // Hold the offer amount in escrow (12% commission on release).
            $this->escrow->hold($order, $store, $price);

            // Open the buyer↔seller conversation for this order.
            Conversation::updateOrCreate(
                ['buyer_id' => $buyer->id, 'store_id' => $store->id, 'order_id' => $order->id],
                ['locked' => false],
            );

            $offer->update(['status' => 'accepted']);
            $offer->smartRequest->update(['status' => SmartRequestStatus::Accepted]);

            return $order;
        });

        return new OrderResource($order->load(['items', 'store']));
    }

    private function nextOrderCode(): string
    {
        return 'A-'.(1043 + Order::whereNull('parent_order_id')->count());
    }

    private function deliveryCode(): string
    {
        if (! app()->environment('production')) {
            return (string) config('anaqatuk.otp_dev_code');
        }

        return str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    }
}
