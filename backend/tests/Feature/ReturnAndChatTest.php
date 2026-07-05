<?php

use App\Enums\OrderStatus;
use App\Models\Conversation;
use App\Models\Order;
use App\Services\ExternalDealingFilter;
use App\Services\OrderService;
use App\Services\ReturnService;
use App\Services\WalletService;

/* SPEC §4.2 free return · §4.8 payfirst chat · §4.10 external-dealing filter. */

it('allows a free return for a ready item and refunds the wallet', function () {
    $store = aStore();
    $product = aProduct($store, ['ready' => 310]);
    $buyer = aUser();
    $order = Order::create([
        'code' => 'A-1038', 'buyer_id' => $buyer->id, 'store_id' => $store->id,
        'status' => OrderStatus::Delivered, 'total' => 335, 'delivery_fee' => 25,
        'deposit_total' => 0, 'delivered_at' => now()->subDays(3),
    ]);
    $order->items()->create([
        'store_id' => $store->id, 'product_id' => $product->id, 'title' => $product->title,
        'mode' => 'ready', 'qty' => 1, 'unit_price' => 310, 'line_total' => 310, 'delivery_code' => '1111',
    ]);

    $return = app(ReturnService::class)->request($order, 'المقاس غير مناسب');
    expect($order->fresh()->status)->toBe(OrderStatus::ReturnRequested);

    app(ReturnService::class)->complete($return);
    expect($order->fresh()->status)->toBe(OrderStatus::ReturnedRefunded)
        ->and(app(WalletService::class)->balance($buyer))->toBe(335.00);
});

it('refuses returning a custom item except for a manufacturing defect', function () {
    $store = aStore();
    $product = aProduct($store, ['custom' => 1100]);
    $buyer = aUser();
    $order = Order::create([
        'code' => 'A-1043', 'buyer_id' => $buyer->id, 'store_id' => $store->id,
        'status' => OrderStatus::Delivered, 'total' => 1100, 'delivered_at' => now(),
    ]);
    $order->items()->create([
        'store_id' => $store->id, 'product_id' => $product->id, 'title' => $product->title,
        'mode' => 'custom', 'qty' => 1, 'unit_price' => 1100, 'line_total' => 1100,
    ]);

    expect(fn () => app(ReturnService::class)->request($order, 'غيّرت رأيي'))
        ->toThrow(RuntimeException::class, 'التفصيل الخاص');

    // Manufacturing defect is allowed.
    $return = app(ReturnService::class)->request($order, 'عيب في المنتج');
    expect($return->status)->toBe('requested');
});

it('opens the chat for a payfirst store once payment is in', function () {
    $buyer = aUser();
    $store = aStore(['comm_mode' => 'payfirst']);
    $product = aProduct($store, ['ready' => 1800]);

    $order = app(OrderService::class)->checkout($buyer, [
        ['product_id' => $product->id, 'mode' => 'ready'],
    ], 'mada')['orders'][0];

    $conv = Conversation::where('order_id', $order->id)->first();
    expect($conv)->not->toBeNull()
        ->and($conv->locked)->toBeFalse(); // unlocked after payment
});

it('flags external phone numbers, links and payment keywords in chat', function () {
    $filter = app(ExternalDealingFilter::class);

    expect($filter->inspect('رقمي 0551234567 كلميني')['flagged'])->toBeTrue()
        ->and($filter->inspect('حوّلي على STC Pay')['flagged'])->toBeTrue()
        ->and($filter->inspect('شوفي instagram.com/shop')['flagged'])->toBeTrue()
        ->and($filter->inspect('تمام حبيبتي، أرسلي المقاسات')['flagged'])->toBeFalse();
});
