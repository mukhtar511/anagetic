<?php

use App\Enums\DepositStatus;
use App\Enums\RentalStatus;
use App\Models\Order;
use App\Models\RentalBooking;
use App\Services\WalletService;
use Laravel\Sanctum\Sanctum;

/*
 * SPEC §Phase-4 scenario 2 — full rental until deposit refund, over HTTP:
 * buyer books a rental (deposit held + calendar blocked) → seller confirms the
 * piece returned sound → the full deposit is refunded to the buyer's wallet and
 * the piece becomes bookable again.
 */

it('rents a piece, holds the deposit, then refunds it on a sound return', function () {
    $buyer = aUser();
    $store = aStore();
    $product = aProduct($store, ['rent' => [320, 500]]);

    Sanctum::actingAs($buyer);
    $this->postJson('/api/cart/items', [
        'product_id' => $product->id,
        'mode' => 'rent',
        'occasion_date' => '2026-08-10',
    ])->assertSuccessful();

    $this->postJson('/api/checkout', ['payment_method' => 'mada'])->assertSuccessful();

    $order = Order::where('buyer_id', $buyer->id)->latest('id')->firstOrFail();
    $deposit = $order->deposits()->firstOrFail();
    $booking = RentalBooking::where('order_id', $order->id)->firstOrFail();

    // Deposit held; the calendar is blocked (not yet bookable). SPEC §4.3.
    expect($deposit->status)->toBe(DepositStatus::Held)
        ->and($booking->status)->toBe(RentalStatus::Booked)
        ->and(app(WalletService::class)->balance($buyer))->toBe(0.0);

    // Seller confirms the piece came back sound.
    Sanctum::actingAs($store->user);
    $this->postJson("/api/seller/rentals/{$booking->id}/sound")->assertSuccessful();

    expect($deposit->fresh()->status)->toBe(DepositStatus::Refunded)
        ->and($booking->fresh()->status)->toBe(RentalStatus::Ok)
        ->and(app(WalletService::class)->balance($buyer))->toBe(500.00); // full deposit back
});
