<?php

namespace App\Enums;

/** Featured listing lifecycle — SPEC §6: active → expired (daily scheduler + 24h notice). */
enum FeaturedStatus: string
{
    case Active = 'active';
    case Expired = 'expired';
}
