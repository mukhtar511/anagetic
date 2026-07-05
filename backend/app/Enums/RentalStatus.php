<?php

namespace App\Enums;

/** Rental booking lifecycle — SPEC §6: booked → out → returning → inspection → ok | dispute. */
enum RentalStatus: string
{
    case Booked = 'booked';
    case Out = 'out';
    case Returning = 'returning';
    case Inspection = 'inspection';
    case Ok = 'ok';
    case Dispute = 'dispute';

    public function allowedNext(): array
    {
        return match ($this) {
            self::Booked => [self::Out],
            self::Out => [self::Returning],
            self::Returning => [self::Inspection],
            self::Inspection => [self::Ok, self::Dispute],
            default => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedNext(), true);
    }

    /** The piece is bookable again only after the seller confirms "ok" (SPEC §4.3). */
    public function isAvailableForBooking(): bool
    {
        return $this === self::Ok;
    }
}
