<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Full product page (SPEC §3.2 / §4.8). Colours expose name/hex + a boolean
 * in_stock (qty>0) only — the raw quantity is never leaked (SPEC §4.6).
 */
class ProductDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category,
            'condition' => $this->condition,
            'collection' => $this->collection,
            'is_single_piece' => (bool) $this->is_single_piece,
            'status' => $this->status,
            'region_id' => $this->region_id,
            'images' => $this->images ?? [],
            'original_price' => $this->original_price !== null ? (float) $this->original_price : null,
            'return_policy_ack' => (bool) $this->return_policy_ack,

            'modes' => $this->whenLoaded('modes', fn () => $this->modes->map(fn ($m) => [
                'type' => $m->type->value,
                'price' => (float) $m->price,
                'deposit' => $m->deposit !== null ? (float) $m->deposit : null,
                'prep_time' => $m->prep_time,
                'exec_time' => $m->exec_time,
                'rent_scope' => $m->rent_scope,
            ])->values()),

            // qty is intentionally omitted — only its presence as a boolean. SPEC §4.6.
            'colors' => $this->whenLoaded('colors', fn () => $this->colors->map(fn ($c) => [
                'name' => $c->name,
                'hex' => $c->hex,
                'in_stock' => (int) $c->qty > 0,
            ])->values()),

            'addons' => $this->whenLoaded('addons', fn () => $this->addons->map(fn ($a) => [
                'id' => $a->id,
                'name' => $a->name,
                'price' => (float) $a->price,
            ])->values()),

            'store' => $this->whenLoaded('store', fn () => (new StoreResource($this->store))->resolve($request)),

            'reviews' => $this->when(isset($this->reviews), fn () => collect($this->reviews)->map(fn ($r) => [
                'stars' => $r->stars,
                'chips' => $r->chips ?? [],
                'text' => $r->text,
                'buyer' => $r->buyer?->name,
                'verified_type' => $r->verified_type,
                'created_at' => $r->created_at,
            ])->values()),
            'rating_avg' => $this->when(
                $this->relationLoaded('store'),
                fn () => (float) $this->store->rating_avg
            ),
        ];
    }
}
