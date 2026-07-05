<?php

namespace App\Http\Resources;

use App\Enums\ProductMode;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Catalog card shape. NEVER exposes product_colors.qty (SPEC §4.6) — colours,
 * when present, carry only name/hex + an in_stock boolean.
 */
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $modes = $this->whenLoaded('modes');
        $minPrice = $this->modes_min_price
            ?? ($this->relationLoaded('modes') ? $this->modes->min('price') : null);

        return [
            'id' => $this->id,
            'code' => $this->code,
            'title' => $this->title,
            'category' => $this->category,
            'condition' => $this->condition,
            'is_single_piece' => (bool) $this->is_single_piece,
            'status' => $this->status,
            'region_id' => $this->region_id,
            'images' => $this->images ?? [],
            'price' => $minPrice !== null ? (float) $minPrice : null,
            'original_price' => $this->original_price !== null ? (float) $this->original_price : null,
            'on_sale' => $this->original_price !== null,
            'modes' => $this->when($this->relationLoaded('modes'), fn () => $modes->map(fn ($m) => [
                'type' => $m->type->value,
                'price' => (float) $m->price,
                'deposit' => $m->deposit !== null ? (float) $m->deposit : null,
            ])->values()),
            'has_rent' => $this->when(
                $this->relationLoaded('modes'),
                fn () => $this->modes->contains('type', ProductMode::Rent)
            ),
            'store' => $this->whenLoaded('store', fn () => [
                'id' => $this->store->id,
                'name' => $this->store->name,
                'slug' => $this->store->slug,
                'rating_avg' => (float) $this->store->rating_avg,
                'status' => $this->store->status->value,
            ]),
        ];
    }
}
