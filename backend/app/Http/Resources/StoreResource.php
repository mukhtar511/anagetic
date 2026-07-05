<?php

namespace App\Http\Resources;

use App\Enums\CommMode;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Storefront shape. The seller phone is revealed ONLY when comm_mode = call AND
 * the store is verified (SPEC §4.8) — otherwise it is never in the payload.
 */
class StoreResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $revealsPhone = $this->comm_mode === CommMode::Call
            && $this->verification_status === 'verified';

        return array_filter([
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'tagline' => $this->tagline,
            'bio' => $this->bio,
            'region_id' => $this->region_id,
            'status' => $this->status->value,
            'comm_mode' => $this->comm_mode->value,
            'phone' => $revealsPhone ? $this->phone : null,
            'packaging' => $this->packaging ?? [],
            'delivery_flat_price' => (float) $this->delivery_flat_price,
            'delivery_free_km' => $this->delivery_free_km,
            'verification_status' => $this->verification_status,
            'joined_year' => $this->joined_year,
            'rating_avg' => (float) $this->rating_avg,
            'completed_orders' => $this->completed_orders,
            'on_time_rate' => $this->on_time_rate,
        ], fn ($v) => $v !== null);
    }
}
