<?php

namespace App\Enums;

/** Smart request lifecycle — SPEC §6: open → matched → accepted | expired | closed. */
enum SmartRequestStatus: string
{
    case Open = 'open';
    case Matched = 'matched';
    case Accepted = 'accepted';
    case Expired = 'expired';
    case Closed = 'closed';

    public function isActive(): bool
    {
        return in_array($this, [self::Open, self::Matched], true);
    }
}
