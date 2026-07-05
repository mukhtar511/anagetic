<?php

namespace App\Services\Ai;

/**
 * "✨ تعبئة تلقائية من الصورة" — SPEC §3.4 / D-12.
 *
 * Stable interface: receives an uploaded image and returns suggested
 * listing fields. The Mock reproduces the prototype's exact result; a real
 * Vision API driver is swapped in behind this same contract later.
 */
interface AiListingService
{
    /**
     * @return array{title:string,description:string,color:string,color_hex:string,category:string,suggested_price_min:float,suggested_price_max:float,summary:string}
     */
    public function analyze(string $imagePath): array;
}
