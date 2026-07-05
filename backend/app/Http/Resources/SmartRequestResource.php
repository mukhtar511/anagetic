<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SmartRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'description' => $this->description,
            'category' => $this->category,
            'size' => $this->size,
            'budget' => (float) $this->budget,
            'need_by' => $this->need_by,
            'scope' => $this->scope,
            'region_id' => $this->region_id,
            'ref_images' => $this->ref_images ?? [],
            'change_notes' => $this->change_notes,
            'status' => $this->status->value,
            'expires_at' => $this->expires_at,
            'created_at' => $this->created_at,
            'offers' => OfferResource::collection($this->whenLoaded('offers')),
            'offers_count' => $this->when(
                $this->relationLoaded('offers'),
                fn () => $this->offers->count()
            ),
        ];
    }
}
