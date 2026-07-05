<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OfferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'smart_request_id' => $this->smart_request_id,
            'price' => (float) $this->price,
            'message' => $this->message,
            'delivery_note' => $this->delivery_note,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'store' => $this->whenLoaded('store', fn () => [
                'id' => $this->store->id,
                'name' => $this->store->name,
                'slug' => $this->store->slug,
                'rating_avg' => (float) $this->store->rating_avg,
            ]),
        ];
    }
}
