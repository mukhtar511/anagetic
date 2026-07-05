<?php

namespace App\Http\Controllers\Seller;

use App\Enums\CouponKind;
use App\Enums\CouponOwner;
use App\Http\Controllers\Api\ApiController;
use App\Models\Coupon;
use App\Services\CouponService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CouponController extends ApiController
{
    use ResolvesSellerStore;

    public function __construct(private readonly CouponService $coupons) {}

    /** GET /api/seller/coupons */
    public function index(Request $request)
    {
        $store = $this->sellerStore($request);

        return $this->ok(['coupons' => $store->coupons()->latest('id')->get()->map($this->present(...))]);
    }

    /** POST /api/seller/coupons */
    public function store(Request $request)
    {
        $store = $this->sellerStore($request);
        $data = $this->validateInput($request);

        if ($error = $this->coupons->validateSellerCoupon($data['code'], CouponKind::from($data['kind']), (float) $data['value'])) {
            return $this->fail($error);
        }

        $coupon = $store->coupons()->create([
            'owner' => CouponOwner::Store,
            'code' => strtoupper($data['code']),
            'kind' => $data['kind'],
            'value' => $data['value'],
            'cap' => $data['cap'] ?? null,
            'min' => $data['min'] ?? 0,
            'active' => $data['active'] ?? true,
            'note' => $data['note'] ?? null,
        ]);

        return $this->ok(['coupon' => $this->present($coupon)], 201);
    }

    /** PATCH /api/seller/coupons/{coupon} */
    public function update(Request $request, Coupon $coupon)
    {
        $store = $this->sellerStore($request);
        abort_unless($coupon->store_id === $store->id, 403);

        $data = $request->validate([
            'value' => ['nullable', 'numeric'],
            'kind' => ['nullable', Rule::enum(CouponKind::class)],
            'cap' => ['nullable', 'numeric'],
            'min' => ['nullable', 'numeric'],
            'active' => ['nullable', 'boolean'],
            'note' => ['nullable', 'string'],
        ]);

        if (isset($data['value']) || isset($data['kind'])) {
            $kind = CouponKind::from($data['kind'] ?? $coupon->kind->value);
            $value = (float) ($data['value'] ?? $coupon->value);
            if ($error = $this->coupons->validateSellerCoupon($coupon->code, $kind, $value)) {
                return $this->fail($error);
            }
        }

        $coupon->update(array_filter($data, fn ($v) => $v !== null));

        return $this->ok(['coupon' => $this->present($coupon->fresh())]);
    }

    /** DELETE /api/seller/coupons/{coupon} */
    public function destroy(Request $request, Coupon $coupon)
    {
        $store = $this->sellerStore($request);
        abort_unless($coupon->store_id === $store->id, 403);
        $coupon->delete();

        return $this->ok(['message' => 'تم حذف الكوبون']);
    }

    private function validateInput(Request $request): array
    {
        return $request->validate([
            'code' => ['required', 'string'],
            'kind' => ['required', Rule::enum(CouponKind::class)],
            'value' => ['required', 'numeric'],
            'cap' => ['nullable', 'numeric'],
            'min' => ['nullable', 'numeric'],
            'active' => ['nullable', 'boolean'],
            'note' => ['nullable', 'string'],
        ]);
    }

    private function present(Coupon $coupon): array
    {
        return [
            'id' => $coupon->id,
            'code' => $coupon->code,
            'kind' => $coupon->kind->value,
            'value' => (float) $coupon->value,
            'cap' => $coupon->cap !== null ? (float) $coupon->cap : null,
            'min' => (float) $coupon->min,
            'active' => (bool) $coupon->active,
            'note' => $coupon->note,
        ];
    }
}
