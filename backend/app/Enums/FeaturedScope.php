<?php

namespace App\Enums;

/** Featured-ad geographic scope — SPEC §4.4. */
enum FeaturedScope: string
{
    case Region = 'region';
    case All = 'all';
}
