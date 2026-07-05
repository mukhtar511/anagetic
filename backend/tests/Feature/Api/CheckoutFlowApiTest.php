<?php

use App\Services\WalletService;
use Laravel\Sanctum\Sanctum;

/* SPEC §4.1 — checkout holds escrow; the seller's delivery code releases the net. */

it('checks out from the cart and releases funds on the seller confirm-delivery code', function () {
    $store = aStore();
    $product = aProduct($store, ['ready' => 1000]);
    $buyer = aUser();

    // Buyer adds to cart + checks out.
    Sanctum::actingAs($buyer);
    $this->postJson('/api/cart/items', [
        'product_id' => $product->id,
        'mode' => 'ready',
        'qty' => 1,
    ])->assertCreated();

    $checkout = $this->postJson('/api/checkout', ['payment_method' => 'mada'])->assertCreated();
    $orderId = $checkout->json('orders.0.id');
    $code = $checkout->json('orders.0.delivery_code');
    expect($code)->not->toBeNull();

    // Nothing has reached the seller yet.
    $seller = $store->user;
    expect(app(WalletService::class)->balance($seller))->toBe(0.0);

    // Seller confirms delivery with the code → net (88% of 1000 = 880) is released.
    Sanctum::actingAs($seller);
    $this->postJson("/api/seller/orders/{$orderId}/confirm-delivery", ['code' => $code])
        ->assertOk()
        ->assertJsonPath('data.status', 'completed');

    expect(app(WalletService::class)->balance($seller))->toBe(880.00);
});

it('rejects a wrong delivery code with 422', function () {
    $store = aStore();
    $product = aProduct($store, ['ready' => 500]);
    $buyer = aUser();

    Sanctum::actingAs($buyer);
    $this->postJson('/api/cart/items', ['product_id' => $product->id, 'mode' => 'ready'])->assertCreated();
    $orderId = $this->postJson('/api/checkout', ['payment_method' => 'mada'])->json('orders.0.id');

    Sanctum::actingAs($store->user);
    $this->postJson("/api/seller/orders/{$orderId}/confirm-delivery", ['code' => '0000'])
        ->assertStatus(422);
});
