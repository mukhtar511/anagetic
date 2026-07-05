<?php

namespace App\Http\Controllers\Api;

use App\Enums\ProductMode;
use App\Http\Requests\AddCartItemRequest;
use App\Http\Resources\CartResource;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends ApiController
{
    /** GET /api/cart */
    public function show(Request $request)
    {
        return new CartResource($this->cartFor($request)->load(['items.product', 'coupon']));
    }

    /** POST /api/cart/items */
    public function addItem(AddCartItemRequest $request)
    {
        $cart = $this->cartFor($request);

        /** @var Product $product */
        $product = Product::with('modes')->findOrFail($request->product_id);
        $mode = $product->modes->firstWhere('type', ProductMode::from($request->mode));
        abort_if($mode === null, 422, 'نمط بيع غير متاح لهذا المنتج');

        // occasion_date has no column of its own — stash it in the measurements bag.
        $measurements = $request->measurements ?? [];
        if ($request->occasion_date) {
            $measurements['occasion_date'] = $request->occasion_date;
        }

        $cart->items()->create([
            'product_id' => $product->id,
            'store_id' => $product->store_id,
            'mode' => $mode->type->value,
            'color' => $request->color,
            'measurements' => $measurements ?: null,
            'notes' => $request->notes,
            'addons' => $request->addon_ids ?? [],
            'packaging' => $request->packaging,
            'qty' => max(1, (int) ($request->qty ?? 1)),
            'unit_price' => $mode->price,
            'deposit' => $mode->type === ProductMode::Rent ? (float) $mode->deposit : 0,
        ]);

        return (new CartResource($cart->fresh()->load(['items.product', 'coupon'])))
            ->response()->setStatusCode(201);
    }

    /** DELETE /api/cart/items/{cartItem} */
    public function removeItem(Request $request, CartItem $cartItem)
    {
        abort_unless($cartItem->cart->user_id === $request->user()->id, 403);
        $cartItem->delete();

        return $this->ok(['message' => 'حُذف من السلة']);
    }

    /** POST /api/cart/coupon */
    public function applyCoupon(Request $request)
    {
        $data = $request->validate(['code' => ['required', 'string']]);
        $coupon = Coupon::where('code', strtoupper($data['code']))->where('active', true)->first();
        abort_if($coupon === null, 422, '✕ الكوبون غير صحيح أو غير مفعّل');

        $cart = $this->cartFor($request);
        $cart->update(['coupon_id' => $coupon->id]);

        return new CartResource($cart->fresh()->load(['items.product', 'coupon']));
    }

    /** DELETE /api/cart/coupon */
    public function removeCoupon(Request $request)
    {
        $cart = $this->cartFor($request);
        $cart->update(['coupon_id' => null]);

        return new CartResource($cart->fresh()->load(['items.product', 'coupon']));
    }

    private function cartFor(Request $request): Cart
    {
        return Cart::firstOrCreate(['user_id' => $request->user()->id]);
    }
}
