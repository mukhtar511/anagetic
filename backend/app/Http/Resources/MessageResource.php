<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'sender_id' => $this->sender_id,
            'type' => $this->type,
            'body' => $this->body,
            'flagged' => (bool) $this->flagged,
            'created_at' => $this->created_at,
            'addon_offer' => $this->whenLoaded('addonOffer', fn () => $this->addonOffer ? [
                'id' => $this->addonOffer->id,
                'name' => $this->addonOffer->name,
                'price' => (float) $this->addonOffer->price,
                'status' => $this->addonOffer->status,
            ] : null),
        ];
    }
}
