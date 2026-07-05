<?php

namespace App\Http\Controllers\Seller;

use App\Enums\SmartRequestStatus;
use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\SmartRequestResource;
use App\Models\SmartRequest;
use App\Services\SmartRequestService;
use Illuminate\Http\Request;

class SmartRequestController extends ApiController
{
    use ResolvesSellerStore;

    public function __construct(private readonly SmartRequestService $service) {}

    /** GET /api/seller/smart-requests — open requests the store can bid on. */
    public function index(Request $request)
    {
        $store = $this->sellerStore($request);

        $requests = SmartRequest::query()
            ->whereIn('status', [SmartRequestStatus::Open->value, SmartRequestStatus::Matched->value])
            ->where('buyer_id', '!=', $request->user()->id)
            ->where(function ($q) use ($store) {
                $q->where('scope', 'all')
                    ->orWhere('region_id', $store->region_id);
            })
            ->with('offers')
            ->latest('id')
            ->get();

        return SmartRequestResource::collection($requests);
    }

    /** POST /api/smart-requests/{smartRequest}/offers */
    public function submitOffer(Request $request, SmartRequest $smartRequest)
    {
        $store = $this->sellerStore($request);
        $data = $request->validate([
            'price' => ['required', 'numeric', 'min:0'],
            'message' => ['nullable', 'string'],
            'delivery_note' => ['nullable', 'string'],
        ]);

        // Over-budget offers throw RuntimeException with their Arabic message → 422.
        $offer = $this->service->submitOffer(
            $smartRequest,
            $store,
            (float) $data['price'],
            $data['message'] ?? null,
            $data['delivery_note'] ?? null,
        );

        return $this->ok([
            'message' => 'تم إرسال عرضك',
            'offer' => [
                'id' => $offer->id,
                'price' => (float) $offer->price,
                'status' => $offer->status,
            ],
        ], 201);
    }
}
