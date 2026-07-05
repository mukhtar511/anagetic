<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\WalletEntryResource;
use App\Services\WalletService;
use App\Services\WithdrawalService;
use Illuminate\Http\Request;

class WalletController extends ApiController
{
    public function __construct(
        private readonly WalletService $wallet,
        private readonly WithdrawalService $withdrawals,
    ) {}

    /** GET /api/wallet */
    public function show(Request $request)
    {
        $wallet = $this->wallet->for($request->user());

        return $this->ok([
            'balance' => (float) $wallet->balance,
            'iban' => $wallet->iban,
            'bank_name' => $wallet->bank_name,
            'ledger' => WalletEntryResource::collection(
                $wallet->entries()->latest('id')->get()
            ),
        ]);
    }

    /** POST /api/wallet/withdraw */
    public function withdraw(Request $request)
    {
        $data = $request->validate(['amount' => ['required', 'numeric', 'min:0']]);

        // RuntimeException → 422 globally.
        $this->withdrawals->withdraw($request->user(), (float) $data['amount']);

        return $this->ok([
            'message' => 'تم تنفيذ السحب',
            'balance' => $this->wallet->balance($request->user()),
        ]);
    }
}
