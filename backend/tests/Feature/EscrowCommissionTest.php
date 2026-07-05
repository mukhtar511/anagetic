<?php

use App\Enums\OrderStatus;
use App\Services\EscrowService;
use App\Services\OrderService;
use App\Services\WalletService;

/*
 * SPEC §4.1 — Escrow + 12% commission, computed on the price AFTER the seller
 * coupon; funds reach the seller only via the delivery code.
 */

it('holds funds and releases the net (88%) only on the correct delivery code', function () {
    $buyer = aUser();
    $store = aStore();
    $product = aProduct($store, ['ready' => 1000]);

    $result = app(OrderService::class)->checkout($buyer, [
        ['product_id' => $product->id, 'mode' => 'ready', 'qty' => 1],
    ], 'mada');

    $order = $result['orders'][0];
    expect($order->status)->toBe(OrderStatus::PaidEscrow);

    $seller = $store->user;
    expect(app(WalletService::class)->balance($seller))->toBe(0.0); // nothing before the code

    // Wrong code is rejected.
    expect(fn () => app(EscrowService::class)->releaseByCode($order, '9999'))
        ->toThrow(RuntimeException::class);

    // Correct code releases net = 1000 * 0.88 = 880.
    $code = $order->items()->value('delivery_code');
    app(EscrowService::class)->releaseByCode($order, $code);

    expect(app(WalletService::class)->balance($seller))->toBe(880.00)
        ->and($order->fresh()->status)->toBe(OrderStatus::Completed);
});

it('computes commission on the price AFTER the seller coupon', function () {
    $buyer = aUser();
    $store = aStore();
    $product = aProduct($store, ['ready' => 1000]);
    $coupon = aStoreCoupon($store, ['code' => 'NOURA100', 'kind' => 'fix', 'value' => 100, 'min' => 500]);

    $order = app(OrderService::class)->checkout($buyer, [
        ['product_id' => $product->id, 'mode' => 'ready', 'qty' => 1],
    ], 'mada', 'NOURA100')['orders'][0];

    $tx = $order->escrowTransactions()->first();

    // Base after coupon = 900. commission = 108. net = 792.
    expect((float) $tx->held_amount)->toBe(900.00)
        ->and((float) $tx->commission)->toBe(108.00)
        ->and((float) $tx->net_amount)->toBe(792.00);

    $code = $order->items()->value('delivery_code');
    app(EscrowService::class)->releaseByCode($order, $code);

    expect(app(WalletService::class)->balance($store->user))->toBe(792.00);
});

it('splits a multi-seller checkout into separate orders each with its own delivery code', function () {
    $buyer = aUser();
    $storeA = aStore(['slug' => 'a-'.uniqid()]);
    $storeB = aStore(['user' => aUser(['name' => 'ريم']), 'name' => 'ريم كوتور', 'slug' => 'b-'.uniqid()]);
    $pA = aProduct($storeA, ['ready' => 500]);
    $pB = aProduct($storeB, ['ready' => 300]);

    $res = app(OrderService::class)->checkout($buyer, [
        ['product_id' => $pA->id, 'mode' => 'ready'],
        ['product_id' => $pB->id, 'mode' => 'ready'],
    ], 'mada');

    expect($res['parent'])->not->toBeNull()
        ->and($res['orders'])->toHaveCount(2);

    $codes = collect($res['orders'])->map(fn ($o) => $o->items()->value('delivery_code'));
    $stores = collect($res['orders'])->map(fn ($o) => $o->store_id)->unique();
    expect($stores)->toHaveCount(2);
    expect($res['orders'][0]->code)->toContain('-A')
        ->and($res['orders'][1]->code)->toContain('-B');
});
