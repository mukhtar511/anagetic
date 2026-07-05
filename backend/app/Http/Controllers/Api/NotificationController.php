<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\NotificationResource;
use App\Models\NotificationCenter;
use Illuminate\Http\Request;

class NotificationController extends ApiController
{
    /** GET /api/notifications */
    public function index(Request $request)
    {
        $items = NotificationCenter::where('user_id', $request->user()->id)
            ->latest('id')
            ->get();

        return $this->ok([
            'unread_count' => $items->whereNull('read_at')->count(),
            'notifications' => NotificationResource::collection($items),
        ]);
    }

    /** POST /api/notifications/read-all */
    public function readAll(Request $request)
    {
        NotificationCenter::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return $this->ok(['message' => 'تم تعليم الكل كمقروء']);
    }

    /** POST /api/notifications/{notification}/read */
    public function read(Request $request, NotificationCenter $notification)
    {
        abort_unless($notification->user_id === $request->user()->id, 403);
        $notification->update(['read_at' => now()]);

        return new NotificationResource($notification->fresh());
    }
}
