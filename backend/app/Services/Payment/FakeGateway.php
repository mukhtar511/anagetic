<?php

namespace App\Services\Payment;

use Illuminate\Support\Str;

/**
 * Fully-functional dev gateway. Every operation "succeeds" and returns a
 * traceable reference so flows can run end-to-end without a real provider.
 */
class FakeGateway implements PaymentGateway
{
    public function authorize(float $amount, string $method, array $meta = []): PaymentResult
    {
        return PaymentResult::ok('AUTH_'.Str::upper(Str::random(10)), $amount, 'authorized via '.$method);
    }

    public function capture(string $reference, float $amount): PaymentResult
    {
        return PaymentResult::ok('CAP_'.Str::upper(Str::random(10)), $amount, 'captured '.$reference);
    }

    public function refund(string $reference, float $amount): PaymentResult
    {
        return PaymentResult::ok('REF_'.Str::upper(Str::random(10)), $amount, 'refunded '.$reference);
    }

    public function payout(string $iban, float $amount, array $meta = []): PaymentResult
    {
        return PaymentResult::ok('PAYOUT_'.Str::upper(Str::random(10)), $amount, 'payout to '.$iban);
    }
}
