<?php

namespace App\Http\Controllers\Seller;

use App\Enums\FeaturedScope;
use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\FeaturedResource;
use App\Models\Product;
use App\Services\FeaturedService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FeaturedController extends ApiController
{
    public function __construct(private readonly FeaturedService $featured) {}

    /** POST /api/products/{product}/featured */
    public function store(Request $request, Product $product)
    {
        abort_unless($product->store->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'scope' => ['required', Rule::enum(FeaturedScope::class)],
            'days' => ['required', 'integer', Rule::in(config('anaqatuk.featured_durations'))],
            'pay_via' => ['nullable', 'string', 'in:wallet,card'],
        ]);

        // RuntimeException (insufficient balance) → 422 globally.
        $placement = $this->featured->purchase(
            $product,
            FeaturedScope::from($data['scope']),
            (int) $data['days'],
            $request->user(),
            $data['pay_via'] ?? 'wallet',
        );

        return new FeaturedResource($placement->load('product.modes', 'product.store'));
    }
}
