<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FeaturedResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'scope' => $this->scope->value,
            'days' => $this->days,
            'starts_at' => $this->starts_at,
            'expires_at' => $this->expires_at,
            'status' => $this->status->value,
            'product' => new ProductResource($this->whenLoaded('product')),
        ];
    }
}
