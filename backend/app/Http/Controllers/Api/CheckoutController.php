<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\OrderResource;
use App\Models\Cart;
use App\Services\OrderService;
use Illuminate\Http\Request;

class CheckoutController extends ApiController
{
    public function __construct(private readonly OrderService $orders) {}

    /** POST /api/checkout */
    public function store(Request $request)
    {
        $data = $request->validate([
            'payment_method' => ['required', 'string'],
            'coupon_code' => ['nullable', 'string'],
        ]);

        $cart = Cart::with(['items', 'coupon'])->firstOrCreate(['user_id' => $request->user()->id]);
        abort_if($cart->items->isEmpty(), 422, 'السلة فارغة');

        $items = $cart->items->map(fn ($i) => [
            'product_id' => $i->product_id,
            'mode' => $i->mode->value,
            'color' => $i->color,
            'qty' => $i->qty,
            'addon_ids' => $i->addons ?? [],
            'packaging' => $i->packaging,
            'measurements' => $i->measurements,
            'notes' => $i->notes,
            'occasion_date' => data_get($i->measurements, 'occasion_date'),
        ])->all();

        $couponCode = $data['coupon_code'] ?? $cart->coupon?->code;

        // RuntimeException from the service is mapped to 422 globally.
        $result = $this->orders->checkout($request->user(), $items, $data['payment_method'], $couponCode);

        // Clear the cart on success.
        $cart->items()->delete();
        $cart->update(['coupon_id' => null]);

        return $this->ok([
            'parent_order_id' => $result['parent']?->id,
            'orders' => OrderResource::collection(
                collect($result['orders'])->map(fn ($o) => $o->load(['items', 'store']))
            ),
        ], 201);
    }
}
