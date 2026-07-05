<?php

namespace App\Http\Controllers\Api;

use App\Enums\ProductMode;
use App\Enums\StoreStatus;
use App\Http\Resources\FeaturedResource;
use App\Http\Resources\ProductDetailResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\RegionResource;
use App\Http\Resources\StoreResource;
use App\Models\ListingFeatured;
use App\Models\Product;
use App\Models\Region;
use App\Models\Store;
use Illuminate\Http\Request;

/** Public catalog — regions, products, featured, stores. */
class CatalogController extends ApiController
{
    /** GET /api/regions */
    public function regions()
    {
        return RegionResource::collection(Region::orderBy('id')->get());
    }

    /** GET /api/products */
    public function products(Request $request)
    {
        $query = Product::query()
            ->with(['modes', 'store'])
            ->withMin('modes', 'price')
            // Hide products whose store is closed. SPEC §4.
            ->whereHas('store', fn ($q) => $q->where('status', '!=', StoreStatus::Closed->value));

        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }
        if ($regionId = $request->query('region_id')) {
            $query->where('region_id', $regionId);
        }
        if ($storeId = $request->query('store_id')) {
            $query->where('store_id', $storeId);
        }
        if (($min = $request->query('min')) !== null) {
            $query->whereHas('modes', fn ($q) => $q->where('price', '>=', (float) $min));
        }
        if (($max = $request->query('max')) !== null) {
            $query->whereHas('modes', fn ($q) => $q->where('price', '<=', (float) $max));
        }

        if ($q = $request->query('q')) {
            $query->where(function ($sub) use ($q) {
                $sub->where('title', 'like', "%{$q}%")
                    ->orWhereHas('store', fn ($s) => $s->where('name', 'like', "%{$q}%"));
            });
        }

        match ($request->query('tag')) {
            'sale' => $query->whereNotNull('original_price'),
            'rent' => $query->whereHas('modes', fn ($q) => $q->where('type', ProductMode::Rent->value)),
            'used' => $query->where('category', 'used'),
            'nobrand' => $query->where('category', 'nobrand'),
            default => null,
        };

        // Buyer's region first when asked.
        if ($nearRegion = $request->query('near_region_id')) {
            $query->orderByRaw('(region_id = ?) desc', [$nearRegion]);
        }

        match ($request->query('sort')) {
            'plow' => $query->orderBy('modes_min_price'),
            'phigh' => $query->orderByDesc('modes_min_price'),
            'rate' => $query->orderByDesc(
                Store::select('rating_avg')->whereColumn('stores.id', 'products.store_id')
            ),
            default => $query->latest('id'), // 'new'
        };

        return ProductResource::collection($query->paginate(20));
    }

    /** GET /api/products/{product} */
    public function product(Product $product)
    {
        $product->load(['modes', 'colors', 'addons', 'store']);

        // Verified purchase/rental reviews for this product's store. SPEC §3.2.
        $product->reviews = $product->store->ratings()
            ->with('buyer')
            ->latest()
            ->take(20)
            ->get();

        return new ProductDetailResource($product);
    }

    /** GET /api/featured */
    public function featured(Request $request)
    {
        $region = $request->query('region');

        $featured = ListingFeatured::query()
            ->with(['product.modes', 'product.store'])
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->when($region, fn ($q) => $q->where(function ($sub) use ($region) {
                $sub->where('scope', 'all')
                    ->orWhereHas('product', fn ($p) => $p->where('region_id', $region));
            }))
            ->latest('id')
            ->get();

        return FeaturedResource::collection($featured);
    }

    /** GET /api/stores */
    public function stores()
    {
        $stores = Store::query()
            ->where('status', '!=', StoreStatus::Closed->value)
            ->orderByDesc('rating_avg')
            ->get();

        return StoreResource::collection($stores);
    }

    /** GET /api/stores/{store} */
    public function store(Store $store)
    {
        $products = $store->products()
            ->with(['modes', 'store'])
            ->withMin('modes', 'price')
            ->where('status', '!=', 'draft')
            ->latest('id')
            ->get();

        return $this->ok([
            'store' => (new StoreResource($store))->resolve(request()),
            'products' => ProductResource::collection($products),
        ]);
    }
}
