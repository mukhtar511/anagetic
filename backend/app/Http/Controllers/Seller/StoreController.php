<?php

namespace App\Http\Controllers\Seller;

use App\Enums\CommMode;
use App\Enums\StoreStatus;
use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\StoreResource;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StoreController extends ApiController
{
    use ResolvesSellerStore;

    /** PUT /api/store — update status, comm_mode, packaging and delivery settings. */
    public function update(Request $request)
    {
        $store = $this->sellerStore($request);

        $data = $request->validate([
            'status' => ['nullable', Rule::enum(StoreStatus::class)],
            'comm_mode' => ['nullable', Rule::enum(CommMode::class)],
            'phone_visible' => ['nullable', 'boolean'],
            'phone' => ['nullable', 'string'],
            'tagline' => ['nullable', 'string'],
            'bio' => ['nullable', 'string'],
            'packaging' => ['nullable', 'array'],
            'delivery_free_km' => ['nullable', 'integer', 'min:0'],
            'delivery_flat_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $store->update(array_filter($data, fn ($v) => $v !== null));

        return new StoreResource($store->fresh());
    }
}
