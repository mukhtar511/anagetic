<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Enums\ProductMode;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\EscrowService;
use App\Services\ReturnService;
use Illuminate\Http\Request;

class OrderController extends ApiController
{
    public function __construct(
        private readonly EscrowService $escrow,
        private readonly ReturnService $returns,
    ) {}

    /** GET /api/orders?status=all|active|done|cancel */
    public function index(Request $request)
    {
        $query = Order::query()
            ->where('buyer_id', $request->user()->id)
            ->whereNotNull('store_id') // the real per-store orders (not the parent wrapper)
            ->with(['items', 'store'])
            ->latest('id');

        match ($request->query('status', 'all')) {
            'active' => $query->whereIn('status', [
                OrderStatus::PendingPayment->value, OrderStatus::PaidEscrow->value,
                OrderStatus::Accepted->value, OrderStatus::InProgress->value,
                OrderStatus::WithCourier->value, OrderStatus::Delivered->value,
                OrderStatus::ReturnRequested->value, OrderStatus::ReturnPickup->value,
            ]),
            'done' => $query->whereIn('status', [
                OrderStatus::Completed->value, OrderStatus::ReturnedRefunded->value,
            ]),
            'cancel' => $query->where('status', OrderStatus::Cancelled->value),
            default => null, // all
        };

        return OrderResource::collection($query->paginate(20));
    }

    /** GET /api/orders/{order} */
    public function show(Order $order)
    {
        $this->authorize('view', $order);

        return new OrderResource($order->load(['items', 'store']));
    }

    /** POST /api/orders/{order}/cancel */
    public function cancel(Order $order)
    {
        $this->authorize('update', $order);

        if (! $order->status->isFreelyCancellable()) {
            return $this->fail('لا يمكن الإلغاء المجاني بعد قبول الطلب');
        }

        $this->escrow->refundToBuyer($order, 'إلغاء الطلب');
        $order->update(['status' => OrderStatus::Cancelled, 'cancellable' => false]);

        return new OrderResource($order->fresh()->load(['items', 'store']));
    }

    /** POST /api/orders/{order}/return */
    public function requestReturn(Request $request, Order $order)
    {
        $this->authorize('update', $order);
        $data = $request->validate(['reason' => ['required', 'string']]);

        $return = $this->returns->request($order, $data['reason']);

        return $this->ok([
            'message' => 'تم فتح طلب الإرجاع',
            'return' => [
                'id' => $return->id,
                'reason' => $return->reason,
                'status' => $return->status,
            ],
        ], 201);
    }

    /** POST /api/orders/{order}/rating */
    public function rate(Request $request, Order $order)
    {
        $this->authorize('update', $order);

        if ($order->status !== OrderStatus::Completed) {
            return $this->fail('يمكن التقييم بعد اكتمال الطلب فقط');
        }

        $data = $request->validate([
            'stars' => ['required', 'integer', 'min:1', 'max:5'],
            'chips' => ['nullable', 'array'],
            'text' => ['nullable', 'string'],
        ]);

        $isRental = $order->items()->where('mode', ProductMode::Rent->value)->exists();

        $rating = $order->ratings()->updateOrCreate(
            ['order_id' => $order->id],
            [
                'store_id' => $order->store_id,
                'buyer_id' => $order->buyer_id,
                'stars' => $data['stars'],
                'chips' => $data['chips'] ?? [],
                'text' => $data['text'] ?? null,
                'verified_type' => $isRental ? 'rental' : 'purchase',
            ],
        );

        // Recompute the store's denormalised rating average.
        $store = $order->store;
        $store->update(['rating_avg' => round((float) $store->ratings()->avg('stars'), 2)]);

        return $this->ok([
            'message' => 'شكرًا لتقييمك',
            'rating' => ['id' => $rating->id, 'stars' => $rating->stars],
            'store_rating_avg' => (float) $store->fresh()->rating_avg,
        ], 201);
    }
}
