<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\EscrowService;
use Illuminate\Http\Request;

class OrderController extends ApiController
{
    public function __construct(private readonly EscrowService $escrow) {}

    /** POST /api/seller/orders/{order}/confirm-delivery */
    public function confirmDelivery(Request $request, Order $order)
    {
        $this->authorize('fulfil', $order);
        $data = $request->validate(['code' => ['required', 'string']]);

        // Wrong code throws RuntimeException with its Arabic message → 422 globally.
        $this->escrow->releaseByCode($order, $data['code']);

        return new OrderResource($order->fresh()->load(['items', 'store']));
    }
}
