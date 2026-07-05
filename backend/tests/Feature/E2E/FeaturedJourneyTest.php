<?php

use App\Enums\FeaturedStatus;
use App\Models\ListingFeatured;
use App\Services\WalletService;
use Laravel\Sanctum\Sanctum;

/*
 * SPEC §Phase-4 scenario 5 — featured package until expiry, over HTTP:
 * seller (with wallet funds) buys a featured placement paid from the wallet,
 * then the daily scheduler expires it once its window ends.
 */

it('buys a featured placement from the wallet and auto-expires it', function () {
    $store = aStore();
    $seller = $store->user;
    $product = aProduct($store, ['ready' => 950]);

    // Fund the seller wallet so the package can be paid.
    app(WalletService::class)->credit($seller, 'sale_income', 200, 'رصيد اختبار');

    // Buy a 7-day region package (49 SAR) from the wallet. SPEC §4.4.
    Sanctum::actingAs($seller);
    $this->postJson("/api/products/{$product->id}/featured", [
        'scope' => 'region',
        'days' => 7,
        'pay_via' => 'wallet',
    ])->assertSuccessful();

    $listing = ListingFeatured::where('product_id', $product->id)->firstOrFail();
    expect($listing->status)->toBe(FeaturedStatus::Active)
        ->and((float) $listing->price)->toBe(49.00)
        // Paid from the wallet: 200 - 49 = 151.
        ->and(app(WalletService::class)->balance($seller))->toBe(151.00);

    // The window ends → the daily scheduler command expires it.
    $listing->update(['expires_at' => now()->subDay()]);
    $this->artisan('featured:expire')->assertSuccessful();

    expect($listing->fresh()->status)->toBe(FeaturedStatus::Expired);
});
