<?php

namespace App\Enums;

/** Deposit lifecycle — SPEC §6: held → refunded | partially_cut | fully_cut (+ appealed). */
enum DepositStatus: string
{
    case Held = 'held';
    case Refunded = 'refunded';
    case PartiallyCut = 'partially_cut';
    case FullyCut = 'fully_cut';
    case Appealed = 'appealed';

    public function allowedNext(): array
    {
        return match ($this) {
            self::Held => [self::Refunded, self::PartiallyCut, self::FullyCut],
            self::PartiallyCut, self::FullyCut => [self::Appealed],
            self::Appealed => [self::Refunded, self::PartiallyCut, self::FullyCut],
            default => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedNext(), true);
    }
}
