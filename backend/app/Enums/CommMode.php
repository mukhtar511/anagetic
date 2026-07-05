<?php

namespace App\Enums;

/** Seller contact policy — SPEC §3.8 / §4.8: chat | call | payfirst. */
enum CommMode: string
{
    case Chat = 'chat';
    case Call = 'call';
    case PayFirst = 'payfirst';

    /** Only "call" mode reveals the seller phone, and only for verified sellers (SPEC §4.8). */
    public function revealsPhone(): bool
    {
        return $this === self::Call;
    }

    /** "payfirst" keeps the chat locked until the buyer pays (escrow held). */
    public function chatLockedBeforePayment(): bool
    {
        return $this === self::PayFirst;
    }
}
