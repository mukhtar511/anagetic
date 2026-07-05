<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Api\ApiController;
use App\Services\Ai\AiListingService;
use Illuminate\Http\Request;

class ListingController extends ApiController
{
    public function __construct(private readonly AiListingService $ai) {}

    /** POST /api/listings/ai-fill — "✨ تعبئة تلقائية من الصورة". SPEC §3.4. */
    public function aiFill(Request $request)
    {
        $request->validate(['image' => ['nullable', 'file', 'image']]);

        $path = $request->hasFile('image')
            ? $request->file('image')->getRealPath()
            : '';

        return $this->ok(['suggestion' => $this->ai->analyze($path)]);
    }
}
