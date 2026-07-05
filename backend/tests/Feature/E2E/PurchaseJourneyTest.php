<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\WalletService;
use Laravel\Sanctum\Sanctum;

/*
 * SPEC §Phase-4 scenario 1 — a full purchase-with-addon journey across the
 * HTTP API: buyer adds a custom item to the cart, pays (escrow held), the
 * seller enters the delivery code, and the net (after 12% commission) lands
 * in the seller's unified wallet.
 */

it('walks buy → cart → checkout(escrow) → delivery code → seller payout', function () {
    $buyer = aUser();
    $store = aStore();
    $product = aProduct($store, ['custom' => 1000], ['return_policy_ack' => true]);
    $addon = $product->addons()->create(['name' => 'ذيل للفستان', 'price' => 150]);

    // 1) Buyer adds the custom item (with a priced addon) to the cart.
    Sanctum::actingAs($buyer);
    $this->postJson('/api/cart/items', [
        'product_id' => $product->id,
        'mode' => 'custom',
        'qty' => 1,
        'addon_ids' => [$addon->id],
        'measurements' => ['shoulder' => 40, 'full_length' => 140],
        'notes' => 'الطول يلامس الأرض مع كعب ٧',
    ])->assertSuccessful();

    // 2) Checkout → funds held in escrow, order created.
    $res = $this->postJson('/api/checkout', ['payment_method' => 'mada'])
        ->assertSuccessful();

    $order = Order::where('buyer_id', $buyer->id)->latest('id')->firstOrFail();
    expect($order->status)->toBe(OrderStatus::PaidEscrow);
    // 1000 (custom) + 150 (addon) held for the seller.
    expect((float) $order->escrowTransactions()->first()->held_amount)->toBe(1150.00);

    // Nothing has reached the seller yet.
    expect(app(WalletService::class)->balance($store->user))->toBe(0.0);

    // 3) Seller enters the delivery code the buyer reveals after inspection.
    $code = $order->items()->value('delivery_code');
    Sanctum::actingAs($store->user);

    // Wrong code is refused.
    $this->postJson("/api/seller/orders/{$order->id}/confirm-delivery", ['code' => '0000'])
        ->assertStatus(422);

    // Correct code releases the net (1150 × 0.88 = 1012).
    $this->postJson("/api/seller/orders/{$order->id}/confirm-delivery", ['code' => $code])
        ->assertSuccessful();

    expect($order->fresh()->status)->toBe(OrderStatus::Completed)
        ->and(app(WalletService::class)->balance($store->user))->toBe(1012.00);
});
