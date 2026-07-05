<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\ProductMode;
use App\Models\Order;
use App\Models\ReturnRequest;
use Illuminate\Support\Carbon;
use RuntimeException;

/** Free returns — 7 days, free to buyer. SPEC §4.2. */
class ReturnService
{
    public function __construct(private readonly WalletService $wallet) {}

    /** Days left in the return window since delivery. */
    public function daysLeft(Order $order): int
    {
        if (! $order->delivered_at) {
            return 0;
        }
        $deadline = $order->delivered_at->copy()->addDays((int) config('anaqatuk.return_window_days'));

        return max(0, (int) Carbon::now()->diffInDays($deadline, false));
    }

    /**
     * Open a free return. Custom-tailored items are non-returnable except for a
     * manufacturing defect. SPEC §4.2.
     *
     * @throws RuntimeException when not returnable.
     */
    public function request(Order $order, string $reason): ReturnRequest
    {
        $isCustom = $order->items()->where('mode', ProductMode::Custom->value)->exists();
        if ($isCustom && $reason !== 'عيب في المنتج') {
            throw new RuntimeException('منتجات التفصيل الخاص تُسترجع فقط في حال عيب مصنعي');
        }

        if ($this->daysLeft($order) <= 0 && $order->status === OrderStatus::Completed) {
            throw new RuntimeException('انتهت نافذة الإرجاع المجاني (٧ أيام)');
        }

        $order->update(['status' => OrderStatus::ReturnRequested, 'cancellable' => false]);

        return $order->returns()->create([
            'reason' => $reason,
            'status' => 'requested',
            'timeline' => [['at' => Carbon::now()->toIso8601String(), 'note' => 'طلب إرجاع']],
        ]);
    }

    /** Seller receives the item → refund to the buyer's wallet. SPEC §4.2. */
    public function complete(ReturnRequest $return): void
    {
        $order = $return->order;
        $return->update(['status' => 'refunded']);
        $order->update(['status' => OrderStatus::ReturnedRefunded]);

        $this->wallet->credit(
            $order->buyer,
            'refund',
            (float) ($order->total - $order->deposit_total),
            "↩️ استرداد إرجاع — #{$order->code}",
            $order->code,
        );
    }
}
