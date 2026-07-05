<?php

use App\Enums\CommMode;
use App\Enums\DepositStatus;
use App\Enums\OrderStatus;
use App\Enums\ProductMode;
use App\Enums\RentalStatus;
use App\Enums\StoreStatus;

/* Pure state-machine logic — SPEC §6. No database. */

it('guards order transitions and free cancellation', function () {
    expect(OrderStatus::PaidEscrow->canTransitionTo(OrderStatus::Accepted))->toBeTrue()
        ->and(OrderStatus::PaidEscrow->canTransitionTo(OrderStatus::Completed))->toBeFalse()
        ->and(OrderStatus::Delivered->canTransitionTo(OrderStatus::Completed))->toBeTrue();

    // Free cancellation only before the seller accepts. SPEC §4.1.
    expect(OrderStatus::PaidEscrow->isFreelyCancellable())->toBeTrue()
        ->and(OrderStatus::Accepted->isFreelyCancellable())->toBeFalse()
        ->and(OrderStatus::InProgress->isFreelyCancellable())->toBeFalse();
});

it('only frees a rental piece for booking after a sound return', function () {
    expect(RentalStatus::Ok->isAvailableForBooking())->toBeTrue()
        ->and(RentalStatus::Booked->isAvailableForBooking())->toBeFalse()
        ->and(RentalStatus::Inspection->canTransitionTo(RentalStatus::Ok))->toBeTrue()
        ->and(RentalStatus::Inspection->canTransitionTo(RentalStatus::Dispute))->toBeTrue();
});

it('models deposit outcomes', function () {
    expect(DepositStatus::Held->canTransitionTo(DepositStatus::Refunded))->toBeTrue()
        ->and(DepositStatus::Held->canTransitionTo(DepositStatus::PartiallyCut))->toBeTrue()
        ->and(DepositStatus::PartiallyCut->canTransitionTo(DepositStatus::Appealed))->toBeTrue();
});

it('encodes store visibility and contact policy', function () {
    expect(StoreStatus::Open->acceptsOrders())->toBeTrue()
        ->and(StoreStatus::Paused->acceptsOrders())->toBeFalse()
        ->and(StoreStatus::Closed->isVisibleInSearch())->toBeFalse();

    // Phone shown only in call mode; chat locked before payment only in payfirst. SPEC §4.8.
    expect(CommMode::Call->revealsPhone())->toBeTrue()
        ->and(CommMode::Chat->revealsPhone())->toBeFalse()
        ->and(CommMode::PayFirst->chatLockedBeforePayment())->toBeTrue();
});

it('makes custom items non-returnable except defects', function () {
    // SPEC §4.2.
    expect(ProductMode::Ready->isFreelyReturnable())->toBeTrue()
        ->and(ProductMode::Custom->isFreelyReturnable())->toBeFalse()
        ->and(ProductMode::Rent->requiresDeposit())->toBeTrue()
        ->and(ProductMode::Ready->requiresDeposit())->toBeFalse();
});
