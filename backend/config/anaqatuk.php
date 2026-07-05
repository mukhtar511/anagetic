<?php

/*
 * Central, non-negotiable business constants — SPEC §4.
 * Every money rule reads from here so tests and services never drift.
 */

return [
    // Platform commission on actual sale, charged on release, on price AFTER seller coupon.
    'commission_rate' => 0.12,

    // Escrow / delivery.
    'delivery_code_length' => 4,
    'free_shipping_threshold' => 300.00,

    // Wallet.
    'min_withdrawal' => 100.00,

    // Returns.
    'return_window_days' => 7,

    // Rental.
    'rental_period_days' => 3,
    'rental_return_days' => 2,               // return transit
    'rental_late_fee_rate_per_day' => 0.10, // 10% of deposit per late day
    'rental_inspection_days' => 1,
    'dispute_assessment_hours' => 24,
    'appeal_review_hours_min' => 24,
    'appeal_review_hours_max' => 72,

    // Coupons.
    'coupon_code_regex' => '/^[A-Z0-9]{3,15}$/',
    'coupon_max_pct' => 70,

    // Smart requests.
    'smart_request_ttl_hours' => 24,
    'max_active_smart_requests' => 3,

    // Featured-ad package matrix: scope => (days => price in SAR).
    'featured_prices' => [
        'region' => [3 => 29, 7 => 49, 15 => 79],
        'all' => [3 => 59, 7 => 99, 15 => 149],
    ],
    'featured_durations' => [3, 7, 15],

    // Auth / OTP. The dev code is honoured only when app.env !== 'production'.
    'phone_length' => 9,
    'phone_prefix' => '5',
    'otp_dev_code' => '1111',
    'otp_ttl_minutes' => 10,

    // Branch delivery defaults.
    'branch_delivery_same_city' => 15.00,
    'branch_delivery_other' => 30.00,

    // The 12 supported regions (registration + permanent selector) — SPEC §3.15.
    'regions' => [
        'الرياض', 'جدة', 'مكة المكرمة', 'الدمام', 'الطائف', 'أبها',
        'المدينة المنورة', 'بريدة', 'تبوك', 'حائل', 'جازان', 'نجران',
    ],
];
