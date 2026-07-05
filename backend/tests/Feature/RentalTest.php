<?php

use App\Enums\DepositStatus;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\RentalService;
use App\Services\WalletService;
use Illuminate\Support\Carbon;

/* SPEC §4.3 — rental availability blocking + deposit lifecycle. */

function bookRental($product, $occasion): Order
{
    $buyer = aUser();
    $order = Order::create([
        'code' => 'A-'.rand(2000, 9000),
        'buyer_id' => $buyer->id,
        'store_id' => $product->store_id,
        'status' => OrderStatus::PaidEscrow,
        'total' => 320,
        'deposit_total' => 500,
    ]);
    app(RentalService::class)->book($product, $order, Carbon::parse($occasion));

    return $order;
}

it('blocks a piece for rental + return + inspection and refuses overlapping dates', function () {
    $store = aStore();
    $product = aProduct($store, ['rent' => [320, 500]]);

    $service = app(RentalService::class);
    $occasion = Carbon::parse('2026-07-08');

    expect($service->isAvailable($product, $occasion))->toBeTrue();
    bookRental($product, '2026-07-08');

    // A date inside the block window (rental 3d + return + inspection) is unavailable.
    expect($service->isAvailable($product, Carbon::parse('2026-07-10')))->toBeFalse()
        ->and($service->isAvailable($product, Carbon::parse('2026-07-12')))->toBeFalse();

    // After the inspection day it frees up.
    expect($service->isAvailable($product, Carbon::parse('2026-07-20')))->toBeTrue();

    expect(fn () => $service->book($product, bookRental($product, '2026-07-09'), Carbon::parse('2026-07-09')))
        ->toThrow(RuntimeException::class);
});

it('refunds the full deposit to the buyer wallet when the piece returns sound', function () {
    $store = aStore();
    $product = aProduct($store, ['rent' => [320, 500]]);
    $buyer = aUser();

    $order = Order::create([
        'code' => 'A-1027', 'buyer_id' => $buyer->id, 'store_id' => $store->id,
        'status' => OrderStatus::PaidEscrow, 'total' => 820, 'deposit_total' => 500,
    ]);
    $booking = app(RentalService::class)->book($product, $order, Carbon::parse('2026-07-08'));
    $deposit = $order->deposits()->create([
        'buyer_id' => $buyer->id, 'store_id' => $store->id, 'amount' => 500, 'status' => 'held',
    ]);

    app(RentalService::class)->confirmSound($booking, $deposit);

    expect($deposit->fresh()->status)->toBe(DepositStatus::Refunded)
        ->and(app(WalletService::class)->balance($buyer))->toBe(500.00)
        ->and($booking->fresh()->status->isAvailableForBooking())->toBeTrue();
});

it('splits the deposit on partial damage: cut to seller, remainder to buyer', function () {
    $store = aStore();
    $buyer = aUser();
    $order = Order::create([
        'code' => 'A-0962', 'buyer_id' => $buyer->id, 'store_id' => $store->id,
        'status' => OrderStatus::PaidEscrow, 'total' => 450, 'deposit_total' => 250,
    ]);
    $deposit = $order->deposits()->create([
        'buyer_id' => $buyer->id, 'store_id' => $store->id, 'amount' => 250, 'status' => 'held',
    ]);

    app(RentalService::class)->resolveDamage($deposit, 50, 'بقعة');

    expect($deposit->fresh()->status)->toBe(DepositStatus::PartiallyCut)
        ->and((float) $deposit->fresh()->cut_amount)->toBe(50.00)
        ->and(app(WalletService::class)->balance($buyer))->toBe(200.00)         // remainder
        ->and(app(WalletService::class)->balance($store->user))->toBe(50.00);   // cut to seller
});
