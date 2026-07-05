<?php

use App\Enums\CouponKind;
use App\Enums\FeaturedScope;
use App\Models\Coupon;
use App\Services\CouponService;
use App\Services\FeaturedService;
use App\Services\WalletService;
use App\Services\WithdrawalService;

/* SPEC §4/§5 — coupon math + validation, withdrawal floor, featured pricing. */

it('caps a percentage coupon and honours the minimum order', function () {
    $service = app(CouponService::class);
    $coupon = new Coupon(['owner' => 'platform', 'code' => 'ANAQA10', 'kind' => 'pct', 'value' => 10, 'cap' => 50, 'min' => 100]);

    expect($service->discountFor($coupon, 80))->toBe(0.0)     // below min
        ->and($service->discountFor($coupon, 300))->toBe(30.0) // 10%
        ->and($service->discountFor($coupon, 1000))->toBe(50.0); // capped
});

it('rejects invalid seller coupon codes and percentages over 70', function () {
    $service = app(CouponService::class);

    expect($service->validateSellerCoupon('ab', CouponKind::Fix, 20))->not->toBeNull()      // too short
        ->and($service->validateSellerCoupon('NOURA 20', CouponKind::Fix, 20))->not->toBeNull() // space
        ->and($service->validateSellerCoupon('NOURA20', CouponKind::Pct, 80))->not->toBeNull()  // >70%
        ->and($service->validateSellerCoupon('NOURA20', CouponKind::Pct, 15))->toBeNull();       // valid
});

it('blocks withdrawals below 100 or above the balance', function () {
    $user = aUser();
    app(WalletService::class)->credit($user, 'sale_income', 250, 'test');

    expect(fn () => app(WithdrawalService::class)->withdraw($user, 50))->toThrow(RuntimeException::class);
    expect(fn () => app(WithdrawalService::class)->withdraw($user, 500))->toThrow(RuntimeException::class);

    app(WithdrawalService::class)->withdraw($user, 200);
    expect(app(WalletService::class)->balance($user))->toBe(50.00);
});

it('prices featured packages from the matrix', function () {
    $service = app(FeaturedService::class);

    expect($service->price(FeaturedScope::Region, 7))->toBe(49.0)
        ->and($service->price(FeaturedScope::All, 15))->toBe(149.0)
        ->and($service->price(FeaturedScope::Region, 3))->toBe(29.0);

    expect(fn () => $service->price(FeaturedScope::Region, 30))->toThrow(InvalidArgumentException::class);
});
