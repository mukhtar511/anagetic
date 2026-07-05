<?php

namespace App\Enums;

/** Product selling mode — SPEC §3.2: ready | custom | rent. */
enum ProductMode: string
{
    case Ready = 'ready';
    case Custom = 'custom';
    case Rent = 'rent';

    public function requiresDeposit(): bool
    {
        return $this === self::Rent;
    }

    /** Custom (tailored) items are non-returnable except manufacturing defect (SPEC §4.2). */
    public function isFreelyReturnable(): bool
    {
        return $this !== self::Custom;
    }
}
