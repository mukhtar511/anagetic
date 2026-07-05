<?php

namespace App\Services;

use App\Enums\DepositStatus;
use App\Enums\RentalStatus;
use App\Models\Deposit;
use App\Models\Order;
use App\Models\Product;
use App\Models\RentalBooking;
use Illuminate\Support\Carbon;
use RuntimeException;

/** Rental availability + deposit rules — SPEC §4.3. */
class RentalService
{
    public function __construct(private readonly WalletService $wallet) {}

    /** Total blocked span in days: rental + return transit + inspection. SPEC §4.3. */
    private function blockSpanDays(): int
    {
        return (int) config('anaqatuk.rental_period_days')
            + (int) config('anaqatuk.rental_return_days')
            + (int) config('anaqatuk.rental_inspection_days');
    }

    /**
     * The inclusive [from, to] interval a piece is blocked for a given occasion.
     * `book()` and `isAvailable()` both derive from this so they never drift.
     *
     * @return array{0:Carbon,1:Carbon}
     */
    private function blockInterval(Carbon $occasion): array
    {
        $from = $occasion->copy()->startOfDay();
        $to = $occasion->copy()->addDays($this->blockSpanDays() - 1)->startOfDay(); // last blocked day

        return [$from, $to];
    }

    /**
     * Is a piece free for the given occasion date? The piece is blocked for
     * rental days + return transit + one inspection day, and only becomes
     * bookable again after the seller confirms "ok". SPEC §4.3.
     */
    public function isAvailable(Product $product, Carbon $occasion): bool
    {
        [$newFrom, $newTo] = $this->blockInterval($occasion);

        // Two inclusive intervals overlap iff newFrom <= existing.to AND newTo >= existing.from.
        return ! RentalBooking::where('product_id', $product->id)
            ->whereNotIn('status', [RentalStatus::Ok->value])
            ->where('block_from', '<=', $newTo)
            ->where('block_to', '>=', $newFrom)
            ->exists();
    }

    /** The next date on which the piece is available for booking. */
    public function nextAvailableDate(Product $product): ?Carbon
    {
        $last = RentalBooking::where('product_id', $product->id)
            ->whereNotIn('status', [RentalStatus::Ok->value])
            ->orderByDesc('block_to')
            ->first();

        return $last ? $last->block_to->copy()->addDay() : null;
    }

    /**
     * Book the piece, auto-blocking rental + return + inspection.
     *
     * @throws RuntimeException when unavailable.
     */
    public function book(Product $product, Order $order, Carbon $occasion): RentalBooking
    {
        if (! $this->isAvailable($product, $occasion)) {
            $next = $this->nextAvailableDate($product);
            $hint = $next ? ' — أقرب تاريخ متاح: '.$next->translatedFormat('j F') : '';
            throw new RuntimeException('✕ غير متاح — القطعة محجوزة أو في فترة الإرجاع والفحص'.$hint);
        }

        [$blockFrom, $blockTo] = $this->blockInterval($occasion);
        $inspection = $blockTo->copy(); // inspection is the last blocked day

        return $product->rentalBookings()->create([
            'order_id' => $order->id,
            'occasion_date' => $occasion,
            'block_from' => $blockFrom,
            'block_to' => $blockTo,
            'inspection_day' => $inspection,
            'status' => RentalStatus::Booked,
        ]);
    }

    /**
     * Seller confirms the piece came back sound → full deposit refunded to the
     * buyer's wallet and the piece becomes bookable again. SPEC §4.3.
     */
    public function confirmSound(RentalBooking $booking, Deposit $deposit): void
    {
        $booking->update(['status' => RentalStatus::Ok]);

        $deposit->update(['status' => DepositStatus::Refunded, 'deposit_note' => 'استُرد كاملًا بعد فحص القطعة']);

        $this->wallet->credit(
            $deposit->buyer,
            'deposit_refund',
            (float) $deposit->amount,
            "استرداد تأمين كامل — #{$deposit->order->code}",
            $deposit->order->code,
        );
    }

    /**
     * Damage dispute resolution: admin cuts a portion (or all) of the deposit
     * to the seller; the remainder is refunded to the buyer. SPEC §4.3 / §7.
     */
    public function resolveDamage(Deposit $deposit, float $cut, string $reason): void
    {
        $cut = round(min($cut, (float) $deposit->amount), 2);
        $refund = round((float) $deposit->amount - $cut, 2);

        $deposit->update([
            'status' => $cut >= (float) $deposit->amount ? DepositStatus::FullyCut : DepositStatus::PartiallyCut,
            'cut_amount' => $cut,
            'cut_reason' => $reason,
        ]);

        // Seller receives the cut; buyer gets the remainder.
        if ($cut > 0) {
            $this->wallet->credit($deposit->store->user, 'deposit_cut', $cut, "تعويض تلف تأجير — #{$deposit->order->code} ({$reason})", $deposit->order->code);
        }
        if ($refund > 0) {
            $this->wallet->credit($deposit->buyer, 'deposit_refund', $refund, "استرداد جزئي لتأمين #{$deposit->order->code} (خُصم {$cut} — {$reason})", $deposit->order->code);
        }
    }
}
