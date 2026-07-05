<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\StoreProductRequest;
use App\Http\Resources\ProductDetailResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends ApiController
{
    use ResolvesSellerStore;

    /** POST /api/products */
    public function store(StoreProductRequest $request)
    {
        $store = $this->sellerStore($request);
        $data = $request->validated();

        if (empty($data['return_policy_ack'])) {
            return $this->fail('أُقر بسياسة الاسترجاع');
        }

        $product = DB::transaction(function () use ($store, $data) {
            $product = $store->products()->create([
                'category' => $data['category'],
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'condition' => $data['condition'] ?? 'new',
                'code' => $data['code'] ?? null,
                'collection' => $data['collection'] ?? null,
                'region_id' => $data['region_id'] ?? $store->region_id,
                'status' => $data['status'] ?? 'live',
                'is_single_piece' => $data['is_single_piece'] ?? false,
                'images' => $data['images'] ?? null,
                'original_price' => $data['original_price'] ?? null,
                'return_policy_ack' => true,
            ]);

            $this->syncChildren($product, $data);

            return $product;
        });

        return new ProductDetailResource($product->load(['modes', 'colors', 'addons', 'store']));
    }

    /** PUT /api/products/{product} */
    public function update(StoreProductRequest $request, Product $product)
    {
        $this->authorizeOwnership($request, $product);
        $data = $request->validated();

        DB::transaction(function () use ($product, $data) {
            $product->update(array_filter([
                'category' => $data['category'] ?? null,
                'title' => $data['title'] ?? null,
                'description' => $data['description'] ?? null,
                'condition' => $data['condition'] ?? null,
                'collection' => $data['collection'] ?? null,
                'region_id' => $data['region_id'] ?? null,
                'status' => $data['status'] ?? null,
                'is_single_piece' => $data['is_single_piece'] ?? null,
                'images' => $data['images'] ?? null,
                'original_price' => $data['original_price'] ?? null,
            ], fn ($v) => $v !== null));

            // Replace child collections when supplied.
            $product->modes()->delete();
            $product->colors()->delete();
            $product->addons()->delete();
            $this->syncChildren($product, $data);
        });

        return new ProductDetailResource($product->fresh()->load(['modes', 'colors', 'addons', 'store']));
    }

    /** DELETE /api/products/{product} */
    public function destroy(Request $request, Product $product)
    {
        $this->authorizeOwnership($request, $product);
        $product->delete();

        return $this->ok(['message' => 'تم حذف المنتج']);
    }

    private function syncChildren(Product $product, array $data): void
    {
        foreach ($data['modes'] ?? [] as $mode) {
            $product->modes()->create([
                'type' => $mode['type'],
                'price' => $mode['price'],
                'deposit' => $mode['deposit'] ?? null,
                'prep_time' => $mode['prep_time'] ?? null,
                'exec_time' => $mode['exec_time'] ?? null,
                'rent_scope' => $mode['rent_scope'] ?? null,
            ]);
        }

        foreach ($data['colors'] ?? [] as $color) {
            $product->colors()->create([
                'name' => $color['name'],
                'hex' => $color['hex'] ?? null,
                'qty' => $color['qty'] ?? 0,
            ]);
        }

        foreach ($data['addons'] ?? [] as $addon) {
            $product->addons()->create([
                'name' => $addon['name'],
                'price' => $addon['price'],
            ]);
        }
    }

    private function authorizeOwnership(Request $request, Product $product): void
    {
        abort_unless($product->store->user_id === $request->user()->id, 403);
    }
}
