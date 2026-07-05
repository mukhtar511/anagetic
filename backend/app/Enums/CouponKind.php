<?php

namespace App\Enums;

/** Coupon discount kind — SPEC §5: fix (SAR) | pct (%). */
enum CouponKind: string
{
    case Fix = 'fix';
    case Pct = 'pct';
}
