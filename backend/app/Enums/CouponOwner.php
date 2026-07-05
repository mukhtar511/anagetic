<?php

namespace App\Enums;

/**
 * Coupon owner — SPEC §4.1:
 *  - platform: funded by the platform, never touches seller payout, does NOT change commission base.
 *  - store:    seller discount, applies only to that store's items, commission computed AFTER it.
 */
enum CouponOwner: string
{
    case Platform = 'platform';
    case Store = 'store';
}
