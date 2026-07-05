<?php

namespace App\Http\Controllers\Api;

use App\Enums\SmartRequestStatus;
use App\Http\Requests\StoreSmartRequestRequest;
use App\Http\Resources\SmartRequestResource;
use App\Models\SmartRequest;
use App\Services\SmartRequestService;
use Illuminate\Http\Request;

class SmartRequestController extends ApiController
{
    public function __construct(private readonly SmartRequestService $service) {}

    /** GET /api/smart-requests — the buyer's own requests with their offers. */
    public function index(Request $request)
    {
        $requests = SmartRequest::query()
            ->where('buyer_id', $request->user()->id)
            ->with(['offers.store'])
            ->latest('id')
            ->get();

        return SmartRequestResource::collection($requests);
    }

    /** POST /api/smart-requests */
    public function store(StoreSmartRequestRequest $request)
    {
        // RuntimeException (over the 3 active limit) → 422 globally.
        $smartRequest = $this->service->create($request->user(), $request->validated());

        return new SmartRequestResource($smartRequest->load('offers'));
    }

    /** POST /api/smart-requests/{smartRequest}/close */
    public function close(Request $request, SmartRequest $smartRequest)
    {
        abort_unless($smartRequest->buyer_id === $request->user()->id, 403);
        $smartRequest->update(['status' => SmartRequestStatus::Closed]);

        return new SmartRequestResource($smartRequest->fresh()->load('offers'));
    }
}
