<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'locked' => (bool) $this->locked,
            'created_at' => $this->created_at,
            'buyer' => $this->whenLoaded('buyer', fn () => [
                'id' => $this->buyer->id,
                'name' => $this->buyer->name,
            ]),
            'store' => $this->whenLoaded('store', fn () => [
                'id' => $this->store->id,
                'name' => $this->store->name,
                'slug' => $this->store->slug,
            ]),
            'last_message' => $this->when(
                $this->relationLoaded('messages'),
                fn () => optional($this->messages->last())->only(['id', 'body', 'type', 'created_at'])
            ),
        ];
    }
}
