<?php

use App\Enums\OrderStatus;
use App\Enums\SmartRequestStatus;
use App\Models\Order;
use App\Models\SmartRequest;
use App\Models\SmartRequestOffer;
use Laravel\Sanctum\Sanctum;

/*
 * SPEC §Phase-4 scenario 4 — smart request until acceptance, over HTTP:
 * buyer posts a request → an over-budget seller offer is rejected (422) while a
 * within-budget offer is accepted → accepting it creates an escrow order.
 */

it('posts a request, rejects an over-budget bid, accepts a valid one into an order', function () {
    $buyer = aUser();
    $store = aStore();

    // Buyer posts a smart request (budget 900).
    Sanctum::actingAs($buyer);
    $this->postJson('/api/smart-requests', [
        'type' => 'buy',
        'description' => 'أبي فستان سهرة راقي لحفل الجمعة',
        'category' => 'dress',
        'budget' => 900,
    ])->assertSuccessful();

    $request = SmartRequest::where('buyer_id', $buyer->id)->firstOrFail();

    // Seller offer over budget is refused. SPEC §4.7.
    Sanctum::actingAs($store->user);
    $this->postJson("/api/smart-requests/{$request->id}/offers", ['price' => 950])
        ->assertStatus(422);
    expect($request->offers()->count())->toBe(0);

    // Within-budget offer is accepted.
    $this->postJson("/api/smart-requests/{$request->id}/offers", [
        'price' => 790,
        'message' => 'جاهز بمقاسك خلال يومين',
    ])->assertSuccessful();

    $offer = SmartRequestOffer::where('smart_request_id', $request->id)->firstOrFail();

    // Buyer accepts → an escrow order is created and the request closes.
    Sanctum::actingAs($buyer);
    $this->postJson("/api/offers/{$offer->id}/accept")->assertSuccessful();

    expect($offer->fresh()->status)->toBe('accepted')
        ->and($request->fresh()->status)->toBe(SmartRequestStatus::Accepted);

    $order = Order::where('buyer_id', $buyer->id)->latest('id')->firstOrFail();
    expect($order->status)->toBe(OrderStatus::PaidEscrow)
        ->and((float) $order->total)->toBeGreaterThanOrEqual(790.0);
});
