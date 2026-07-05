<?php

use App\Services\SmartRequestService;
use Laravel\Sanctum\Sanctum;

/* SPEC §4.7 — a seller offer above the request budget is rejected server-side. */

it('rejects a seller offer that exceeds the request budget with 422', function () {
    $buyer = aUser();
    $store = aStore();

    $request = app(SmartRequestService::class)->create($buyer, [
        'type' => 'buy',
        'description' => 'أبي عباية سوداء بسيطة',
        'category' => 'abaya',
        'budget' => 500,
    ]);

    Sanctum::actingAs($store->user);

    // Over budget → 422 with the service's Arabic message.
    $this->postJson("/api/smart-requests/{$request->id}/offers", ['price' => 900])
        ->assertStatus(422)
        ->assertJsonPath('message', fn ($m) => str_contains($m, 'ميزانية'));

    // Within budget → accepted.
    $this->postJson("/api/smart-requests/{$request->id}/offers", ['price' => 480])
        ->assertCreated()
        ->assertJsonPath('offer.status', 'sent');
});
