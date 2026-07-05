<?php

namespace App\Services\Sms;

/** SMS/OTP abstraction — SPEC §1. A real provider is swapped in behind this. */
interface SmsChannel
{
    /** Send an OTP code to a phone. Returns the code actually issued. */
    public function sendOtp(string $phone): string;

    /** Verify a submitted code against what was issued. */
    public function verify(string $phone, string $code): bool;
}
