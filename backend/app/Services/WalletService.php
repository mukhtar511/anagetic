<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletEntry;
use Illuminate\Support\Facades\DB;

/**
 * The single source of truth for money movement. SPEC §3.7 / §6.
 * Balance is always the sum of ledger rows; no field is mutated in isolation.
 */
class WalletService
{
    public function for(User $user): Wallet
    {
        return Wallet::firstOrCreate(['user_id' => $user->id], ['balance' => 0]);
    }

    /**
     * Post a signed ledger entry and keep the cached balance in sync.
     * Amounts are DECIMAL(10,2); never Float in storage. SPEC §5.
     */
    public function post(Wallet $wallet, string $type, float $amount, string $text, ?string $ref = null): WalletEntry
    {
        return DB::transaction(function () use ($wallet, $type, $amount, $text, $ref) {
            $entry = $wallet->entries()->create([
                'type' => $type,
                'amount' => round($amount, 2),
                'text' => $text,
                'ref' => $ref,
            ]);

            $wallet->increment('balance', round($amount, 2));

            return $entry;
        });
    }

    /** Credit (positive) helper. */
    public function credit(User $user, string $type, float $amount, string $text, ?string $ref = null): WalletEntry
    {
        return $this->post($this->for($user), $type, abs(round($amount, 2)), $text, $ref);
    }

    /** Debit (negative) helper. */
    public function debit(User $user, string $type, float $amount, string $text, ?string $ref = null): WalletEntry
    {
        return $this->post($this->for($user), $type, -abs(round($amount, 2)), $text, $ref);
    }

    public function balance(User $user): float
    {
        return (float) $this->for($user)->balance;
    }
}
