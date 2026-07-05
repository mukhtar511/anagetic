<?php

namespace App\Services;

use App\Models\User;
use App\Services\Payment\PaymentGateway;
use RuntimeException;

/** Wallet withdrawals — min 100 SAR, up to the current balance. SPEC §4 / §3.7. */
class WithdrawalService
{
    public function __construct(
        private readonly WalletService $wallet,
        private readonly PaymentGateway $gateway,
    ) {}

    public function withdraw(User $user, float $amount): void
    {
        $min = (float) config('anaqatuk.min_withdrawal');
        $balance = $this->wallet->balance($user);
        $amount = round($amount, 2);

        if ($amount < $min || $amount > $balance) {
            throw new RuntimeException('✕ المبلغ لازم يكون بين ١٠٠ ر.س ورصيدك الحالي ('.rtrim(rtrim(number_format($balance, 2, '.', ''), '0'), '.').' ر.س)');
        }

        $w = $this->wallet->for($user);
        $this->gateway->payout((string) ($w->iban ?? 'SA0000'), $amount, ['user_id' => $user->id]);

        $this->wallet->debit($user, 'withdrawal', $amount, '⬇ سحب لحسابك البنكي — مصرف الراجحي');
    }
}
