<?php

namespace App\Services;

use App\Enums\CouponKind;
use App\Enums\CouponOwner;
use App\Models\Coupon;

/**
 * Coupon math and validation. SPEC §4.1 / §5.
 *
 * Platform coupons are funded by the platform and never reduce the seller
 * payout or the commission base. Seller coupons discount the seller's own
 * items only, and the 12% commission is computed on the price AFTER the
 * seller coupon.
 */
class CouponService
{
    /**
     * Discount for a given base amount (mirrors prototype cpnDiscount()).
     * Returns 0 if the base is below the coupon minimum.
     */
    public function discountFor(Coupon $coupon, float $base): float
    {
        if ($base < (float) $coupon->min) {
            return 0.0;
        }

        $d = $coupon->kind === CouponKind::Pct
            ? $base * ((float) $coupon->value) / 100
            : (float) $coupon->value;

        if ($coupon->cap !== null) {
            $d = min($d, (float) $coupon->cap);
        }

        return round(min($d, $base), 2);
    }

    /**
     * Validate a seller-created coupon's fields (mirrors prototype addCpn()).
     *
     * @return string|null Arabic error message, or null when valid.
     */
    public function validateSellerCoupon(string $code, CouponKind $kind, float $value): ?string
    {
        $code = strtoupper($code);

        if (! preg_match((string) config('anaqatuk.coupon_code_regex'), $code)) {
            return '✕ الكود لازم يكون ٣–١٥ حرفًا إنجليزيًا أو رقمًا بدون مسافات';
        }

        $maxPct = (int) config('anaqatuk.coupon_max_pct');
        if ($value <= 0 || ($kind === CouponKind::Pct && $value > $maxPct)) {
            return '✕ قيمة الخصم غير منطقية (النسبة حتى ٧٠٪ كحد أقصى)';
        }

        return null;
    }

    /**
     * The base on which a store's escrow commission is computed: the store's
     * subtotal minus any *seller* coupon discount. Platform coupons are excluded.
     */
    public function commissionBase(float $storeSubtotal, ?Coupon $coupon): float
    {
        if ($coupon && $coupon->owner === CouponOwner::Store) {
            return round(max(0, $storeSubtotal - $this->discountFor($coupon, $storeSubtotal)), 2);
        }

        return round($storeSubtotal, 2);
    }
}
