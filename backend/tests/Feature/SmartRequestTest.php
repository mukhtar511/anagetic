<?php

use App\Enums\SmartRequestStatus;
use App\Models\SmartRequest;
use App\Services\SmartRequestService;

/* SPEC §4.7 — offer > budget rejected; max 3 active requests. */

it('rejects a seller offer above the request budget and keeps it invisible', function () {
    $buyer = aUser();
    $store = aStore();
    $request = app(SmartRequestService::class)->create($buyer, [
        'type' => 'buy', 'description' => 'أبي فستان سهرة راقي لحفل الجمعة',
        'category' => 'dress', 'budget' => 900,
    ]);

    expect(fn () => app(SmartRequestService::class)->submitOffer($request, $store, 950))
        ->toThrow(RuntimeException::class, 'أعلى من ميزانية الطلب');

    expect($request->offers()->count())->toBe(0);

    // A within-budget offer is accepted and flips the request to matched.
    $offer = app(SmartRequestService::class)->submitOffer($request, $store, 790, 'جاهز بمقاسك');
    expect($offer->price)->toBe('790.00')
        ->and($request->fresh()->status)->toBe(SmartRequestStatus::Matched);
});

it('enforces the 3 active-request limit per user', function () {
    $buyer = aUser();
    foreach (range(1, 3) as $i) {
        app(SmartRequestService::class)->create($buyer, [
            'type' => 'buy', 'description' => "طلب $i", 'category' => 'dress', 'budget' => 500,
        ]);
    }

    expect(SmartRequest::where('buyer_id', $buyer->id)->count())->toBe(3);

    expect(fn () => app(SmartRequestService::class)->create($buyer, [
        'type' => 'buy', 'description' => 'رابع', 'category' => 'dress', 'budget' => 500,
    ]))->toThrow(RuntimeException::class);
});
