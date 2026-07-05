<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Api\ApiController;
use App\Models\Dispute;
use App\Models\RentalBooking;
use App\Services\RentalService;
use Illuminate\Http\Request;

class RentalController extends ApiController
{
    public function __construct(private readonly RentalService $rental) {}

    /** POST /api/seller/rentals/{booking}/sound — piece returned sound → refund deposit. */
    public function sound(Request $request, RentalBooking $booking)
    {
        $this->authorizeOwnership($request, $booking);

        $deposit = $booking->order?->deposits()->where('status', 'held')->first();
        abort_if($deposit === null, 422, 'لا يوجد تأمين محجوز لهذا الحجز');

        $this->rental->confirmSound($booking, $deposit);

        return $this->ok(['message' => 'تم تأكيد سلامة القطعة واسترداد التأمين للعميلة']);
    }

    /** POST /api/seller/rentals/{booking}/dispute — open a damage dispute. */
    public function dispute(Request $request, RentalBooking $booking)
    {
        $this->authorizeOwnership($request, $booking);
        $data = $request->validate([
            'evidence' => ['nullable', 'array'],
            'note' => ['nullable', 'string'],
        ]);

        $deposit = $booking->order?->deposits()->latest('id')->first();

        $dispute = Dispute::create([
            'type' => 'rental_damage',
            'order_id' => $booking->order_id,
            'deposit_id' => $deposit?->id,
            'evidence' => $data['evidence'] ?? null,
            'status' => 'open',
        ]);

        return $this->ok([
            'message' => 'تم فتح نزاع — سيراجعه الفريق خلال ٢٤ ساعة',
            'dispute_id' => $dispute->id,
        ], 201);
    }

    private function authorizeOwnership(Request $request, RentalBooking $booking): void
    {
        abort_unless($booking->product->store->user_id === $request->user()->id, 403);
    }
}
