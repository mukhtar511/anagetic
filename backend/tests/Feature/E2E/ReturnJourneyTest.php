<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\ReturnRequest;
use App\Services\ReturnService;
use App\Services\WalletService;
use Laravel\Sanctum\Sanctum;

/*
 * SPEC §Phase-4 scenario 3 — free return, over HTTP: a delivered ready item is
 * returned within the 7-day window via the API; once the seller receives it the
 * amount is refunded to the buyer's wallet (free to the buyer). SPEC §4.2.
 */

it('requests a free return via the API and refunds the wallet on receipt', function () {
    $buyer = aUser();
    $store = aStore();
    $product = aProduct($store, ['ready' => 310]);

    // A delivered order within the return window.
    $order = Order::create([
        'code' => 'A-1038',
        'buyer_id' => $buyer->id,
        'store_id' => $store->id,
        'status' => OrderStatus::Delivered,
        'subtotal' => 310,
        'delivery_fee' => 25,
        'total' => 335,
        'deposit_total' => 0,
        'delivered_at' => now()->subDays(2),
    ]);
    $order->items()->create([
        'store_id' => $store->id,
        'product_id' => $product->id,
        'title' => $product->title,
        'mode' => 'ready',
        'qty' => 1,
        'unit_price' => 310,
        'line_total' => 310,
        'delivery_code' => '1111',
    ]);

    // Buyer requests a free return through the API.
    Sanctum::actingAs($buyer);
    $this->postJson("/api/orders/{$order->id}/return", ['reason' => 'المقاس غير مناسب'])
        ->assertSuccessful();

    expect($order->fresh()->status)->toBe(OrderStatus::ReturnRequested);

    // Seller receives it (admin/ops side) → refund lands in the buyer wallet.
    app(ReturnService::class)->complete(ReturnRequest::where('order_id', $order->id)->firstOrFail());

    expect($order->fresh()->status)->toBe(OrderStatus::ReturnedRefunded)
        ->and(app(WalletService::class)->balance($buyer))->toBe(335.00);
});
