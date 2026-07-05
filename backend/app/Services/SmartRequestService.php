<?php

namespace App\Services;

use App\Enums\SmartRequestStatus;
use App\Models\SmartRequest;
use App\Models\SmartRequestOffer;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Carbon;
use RuntimeException;

/** Smart requests — SPEC §3.9 / §4.7. */
class SmartRequestService
{
    /** A user may have at most 3 active requests. SPEC §4.7. */
    public function assertUnderActiveLimit(User $buyer): void
    {
        $active = SmartRequest::where('buyer_id', $buyer->id)
            ->whereIn('status', [SmartRequestStatus::Open->value, SmartRequestStatus::Matched->value])
            ->count();

        if ($active >= (int) config('anaqatuk.max_active_smart_requests')) {
            throw new RuntimeException('وصلتِ الحد الأقصى (٣ طلبات نشطة) — أغلقي طلبًا قبل نشر جديد');
        }
    }

    public function create(User $buyer, array $data): SmartRequest
    {
        $this->assertUnderActiveLimit($buyer);

        return SmartRequest::create(array_merge($data, [
            'buyer_id' => $buyer->id,
            'region_id' => $buyer->region_id,
            'status' => SmartRequestStatus::Open,
            'expires_at' => Carbon::now()->addHours((int) config('anaqatuk.smart_request_ttl_hours')),
        ]));
    }

    /**
     * Submit a seller offer. An offer above the request budget is REJECTED
     * server-side and never becomes visible to the buyer. SPEC §4.7.
     *
     * @throws RuntimeException when the offer exceeds the budget.
     */
    public function submitOffer(SmartRequest $request, Store $store, float $price, ?string $message = null, ?string $deliveryNote = null): SmartRequestOffer
    {
        if ($price > (float) $request->budget) {
            throw new RuntimeException('عرضك أعلى من ميزانية الطلب ('.rtrim(rtrim(number_format((float) $request->budget, 2, '.', ''), '0'), '.').' ر.س) — ما راح يظهر للعميلة');
        }

        if (! $request->status->isActive()) {
            throw new RuntimeException('الطلب لم يعد مفتوحًا لاستقبال العروض');
        }

        $offer = $request->offers()->create([
            'store_id' => $store->id,
            'price' => round($price, 2),
            'message' => $message,
            'delivery_note' => $deliveryNote,
            'status' => 'sent',
        ]);

        if ($request->status === SmartRequestStatus::Open) {
            $request->update(['status' => SmartRequestStatus::Matched]);
        }

        return $offer;
    }

    /** Extend the request TTL by another window. SPEC §4.7 (قابلة للتمديد). */
    public function extend(SmartRequest $request): void
    {
        $request->update([
            'expires_at' => Carbon::now()->addHours((int) config('anaqatuk.smart_request_ttl_hours')),
        ]);
    }

    /** Expire requests past their TTL. */
    public function expireDue(): int
    {
        return SmartRequest::whereIn('status', [SmartRequestStatus::Open->value, SmartRequestStatus::Matched->value])
            ->where('expires_at', '<=', Carbon::now())
            ->update(['status' => SmartRequestStatus::Expired->value]);
    }
}
