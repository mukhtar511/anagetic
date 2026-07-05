<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\DepositResource;
use App\Models\AppealTicket;
use App\Models\Deposit;
use Illuminate\Http\Request;

class DepositController extends ApiController
{
    /** GET /api/deposits */
    public function index(Request $request)
    {
        $deposits = Deposit::with('order')
            ->where('buyer_id', $request->user()->id)
            ->latest('id')
            ->get();

        return DepositResource::collection($deposits);
    }

    /** POST /api/deposits/{deposit}/appeal */
    public function appeal(Request $request, Deposit $deposit)
    {
        abort_unless($deposit->buyer_id === $request->user()->id, 403);

        $ref = 'APL-'.$deposit->order->code;

        $ticket = AppealTicket::firstOrCreate(
            ['ref' => $ref],
            [
                'deposit_id' => $deposit->id,
                'user_id' => $request->user()->id,
                'status' => 'open',
            ],
        );

        return $this->ok([
            'message' => 'تم فتح تظلّم — سيُراجَع خلال ٢٤–٧٢ ساعة',
            'ref' => $ticket->ref,
        ], 201);
    }
}
