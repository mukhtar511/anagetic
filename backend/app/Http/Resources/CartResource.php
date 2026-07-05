<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $items = $this->whenLoaded('items');

        return [
            'id' => $this->id,
            'coupon' => $this->whenLoaded('coupon', fn () => $this->coupon ? [
                'code' => $this->coupon->code,
                'kind' => $this->coupon->kind->value,
                'value' => (float) $this->coupon->value,
            ] : null),
            'items' => $this->when($this->relationLoaded('items'), fn () => $items->map(fn ($i) => [
                'id' => $i->id,
                'product_id' => $i->product_id,
                'store_id' => $i->store_id,
                'title' => $i->product?->title,
                'mode' => $i->mode->value,
                'color' => $i->color,
                'qty' => $i->qty,
                'unit_price' => (float) $i->unit_price,
                'deposit' => (float) $i->deposit,
                'addons' => $i->addons ?? [],
                'packaging' => $i->packaging,
                'measurements' => $i->measurements,
                'notes' => $i->notes,
                'line_total' => round((float) $i->unit_price * $i->qty, 2),
            ])->values()),
            'subtotal' => $this->when(
                $this->relationLoaded('items'),
                fn () => round($items->sum(fn ($i) => (float) $i->unit_price * $i->qty), 2)
            ),
        ];
    }
}
