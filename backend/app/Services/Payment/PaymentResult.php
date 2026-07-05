<?php

namespace App\Services\Payment;

/** Immutable outcome of a gateway operation. */
final class PaymentResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $reference,
        public readonly float $amount,
        public readonly ?string $message = null,
    ) {}

    public static function ok(string $reference, float $amount, ?string $message = null): self
    {
        return new self(true, $reference, $amount, $message);
    }

    public static function fail(string $message): self
    {
        return new self(false, '', 0.0, $message);
    }
}
