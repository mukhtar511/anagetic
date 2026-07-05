<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepositResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => (float) $this->amount,
            'status' => $this->status->value,
            'cut_amount' => $this->cut_amount !== null ? (float) $this->cut_amount : null,
            'cut_reason' => $this->cut_reason,
            'deposit_note' => $this->deposit_note,
            'created_at' => $this->created_at,
            'order' => $this->whenLoaded('order', fn () => [
                'id' => $this->order->id,
                'code' => $this->order->code,
            ]),
        ];
    }
}
