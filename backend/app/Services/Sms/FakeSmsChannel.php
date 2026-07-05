<?php

namespace App\Services\Sms;

use App\Models\OtpCode;
use Illuminate\Support\Carbon;

/**
 * Dev SMS driver. In non-production it always issues the fixed dev code
 * (config anaqatuk.otp_dev_code = "1111") and accepts it. SPEC §1 / D-04.
 * The fixed code is refused when app.env === 'production'.
 */
class FakeSmsChannel implements SmsChannel
{
    public function sendOtp(string $phone): string
    {
        $code = $this->issueCode();

        OtpCode::create([
            'phone' => $phone,
            'code' => $code,
            'expires_at' => Carbon::now()->addMinutes((int) config('anaqatuk.otp_ttl_minutes', 10)),
        ]);

        return $code;
    }

    public function verify(string $phone, string $code): bool
    {
        $devCode = (string) config('anaqatuk.otp_dev_code');

        // Accept the dev code outside production without a DB round-trip.
        if (! app()->environment('production') && $code === $devCode) {
            return true;
        }

        $otp = OtpCode::where('phone', $phone)
            ->where('code', $code)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', Carbon::now())
            ->latest('id')
            ->first();

        if (! $otp) {
            return false;
        }

        $otp->update(['consumed_at' => Carbon::now()]);

        return true;
    }

    private function issueCode(): string
    {
        if (! app()->environment('production')) {
            return (string) config('anaqatuk.otp_dev_code');
        }

        return str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    }
}
