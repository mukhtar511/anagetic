<?php

namespace App\Http\Controllers\Api;

use App\Models\AddonOffer;
use Illuminate\Http\Request;

class AddonOfferController extends ApiController
{
    /** POST /api/addon-offers/{addonOffer}/accept — buyer accepts → attach to order. */
    public function accept(Request $request, AddonOffer $addonOffer)
    {
        $order = $this->authorizedOrder($request, $addonOffer);
        abort_if($addonOffer->status !== 'pending', 422, 'العرض لم يعد متاحًا');

        $addonOffer->update(['status' => 'accepted']);

        if ($order) {
            $order->increment('total', (float) $addonOffer->price);
        }

        return $this->ok([
            'message' => 'تمت إضافة العرض للطلب',
            'order_total' => $order ? (float) $order->fresh()->total : null,
        ]);
    }

    /** POST /api/addon-offers/{addonOffer}/reject */
    public function reject(Request $request, AddonOffer $addonOffer)
    {
        $this->authorizedOrder($request, $addonOffer);
        $addonOffer->update(['status' => 'rejected']);

        return $this->ok(['message' => 'تم رفض العرض']);
    }

    /** Only the buyer on the offer's order/conversation may act on it. */
    private function authorizedOrder(Request $request, AddonOffer $addonOffer)
    {
        $addonOffer->load(['order', 'message.conversation']);
        $buyerId = $addonOffer->order?->buyer_id
            ?? $addonOffer->message?->conversation?->buyer_id;

        abort_unless($buyerId === $request->user()->id, 403);

        return $addonOffer->order;
    }
}
