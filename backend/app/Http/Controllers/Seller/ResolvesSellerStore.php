<?php

namespace App\Http\Controllers\Seller;

use App\Models\Store;
use Illuminate\Http\Request;

trait ResolvesSellerStore
{
    /** The authenticated seller's store, or a 403 when they don't own one. */
    protected function sellerStore(Request $request): Store
    {
        $store = $request->user()->store;
        abort_if($store === null, 403, 'لا يوجد متجر مرتبط بحسابك');

        return $store;
    }
}
