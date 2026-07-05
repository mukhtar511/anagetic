<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'parent_order_id' => $this->parent_order_id,
            'subtotal' => (float) $this->subtotal,
            'delivery_fee' => (float) $this->delivery_fee,
            'discount' => (float) $this->discount,
            'deposit_total' => (float) $this->deposit_total,
            'total' => (float) $this->total,
            'is_custom' => (bool) $this->is_custom,
            'payment_method' => $this->payment_method,
            'cancellable' => (bool) $this->cancellable,
            'region_id' => $this->region_id,
            // The buyer's app shows this 4-digit code to the courier/seller on hand-off.
            'delivery_code' => $this->when(
                $this->relationLoaded('items'),
                fn () => $this->items->first()?->delivery_code
            ),
            'placed_at' => $this->placed_at,
            'accepted_at' => $this->accepted_at,
            'delivered_at' => $this->delivered_at,
            'completed_at' => $this->completed_at,
            'store' => $this->whenLoaded('store', fn () => [
                'id' => $this->store->id,
                'name' => $this->store->name,
                'slug' => $this->store->slug,
            ]),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($i) => [
                'id' => $i->id,
                'product_id' => $i->product_id,
                'title' => $i->title,
                'mode' => $i->mode->value,
                'color' => $i->color,
                'qty' => $i->qty,
                'unit_price' => (float) $i->unit_price,
                'addons' => $i->addons ?? [],
                'addons_total' => (float) $i->addons_total,
                'packaging' => $i->packaging,
                'packaging_price' => (float) $i->packaging_price,
                'deposit' => (float) $i->deposit,
                'line_total' => (float) $i->line_total,
            ])->values()),
        ];
    }
}
