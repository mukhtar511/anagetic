<?php

namespace App\Services\Payment;

/**
 * Unified payment abstraction — SPEC §1.
 *
 * No business logic may bind to a concrete provider. The real integration
 * (Moyasar / NeoLeap) is added later behind this same interface; the
 * FakeGateway is a fully-functional dev driver.
 */
interface PaymentGateway
{
    /** Reserve funds (escrow hold) for an order. */
    public function authorize(float $amount, string $method, array $meta = []): PaymentResult;

    /** Capture previously authorized funds. */
    public function capture(string $reference, float $amount): PaymentResult;

    /** Refund captured/authorized funds back to the payer. */
    public function refund(string $reference, float $amount): PaymentResult;

    /** Pay out available balance to a seller bank account (IBAN). */
    public function payout(string $iban, float $amount, array $meta = []): PaymentResult;
}
