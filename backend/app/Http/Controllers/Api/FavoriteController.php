<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\ProductResource;
use App\Models\Favorite;
use App\Models\Product;
use Illuminate\Http\Request;

class FavoriteController extends ApiController
{
    /** GET /api/favorites */
    public function index(Request $request)
    {
        $products = Product::query()
            ->with(['modes', 'store'])
            ->withMin('modes', 'price')
            ->whereHas('favorites', fn ($q) => $q->where('user_id', $request->user()->id))
            ->get();

        return ProductResource::collection($products);
    }

    /** POST /api/favorites */
    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'notify_when_open' => ['nullable', 'boolean'],
        ]);

        Favorite::updateOrCreate(
            ['user_id' => $request->user()->id, 'product_id' => $data['product_id']],
            ['notify_when_open' => $data['notify_when_open'] ?? false],
        );

        return $this->ok(['message' => 'أُضيف للمفضلة'], 201);
    }

    /** DELETE /api/favorites/{product} */
    public function destroy(Request $request, Product $product)
    {
        Favorite::where('user_id', $request->user()->id)
            ->where('product_id', $product->id)
            ->delete();

        return $this->ok(['message' => 'حُذف من المفضلة']);
    }
}
