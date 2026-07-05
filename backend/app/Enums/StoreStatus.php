<?php

namespace App\Enums;

/** Store visibility — SPEC §3.8 / §4: open | paused | closed. */
enum StoreStatus: string
{
    case Open = 'open';
    case Paused = 'paused';
    case Closed = 'closed';

    /** Paused/closed stores cannot take new orders; closed also hides from search. */
    public function acceptsOrders(): bool
    {
        return $this === self::Open;
    }

    public function isVisibleInSearch(): bool
    {
        return $this !== self::Closed;
    }

    public function label(): string
    {
        return match ($this) {
            self::Open => 'المتجر مفتوح',
            self::Paused => 'لا تستقبل طلبات حاليًا',
            self::Closed => 'المتجر مقفل مؤقتًا',
        };
    }
}
