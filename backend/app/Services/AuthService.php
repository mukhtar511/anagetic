<?php

namespace App\Services;

use App\Models\User;
use App\Services\Sms\SmsChannel;
use Illuminate\Support\Carbon;
use RuntimeException;

/** Phone + OTP auth with region capture. SPEC §3.15. */
class AuthService
{
    public function __construct(private readonly SmsChannel $sms) {}

    /** Validate the phone shape and dispatch an OTP. */
    public function requestOtp(string $phone): void
    {
        $length = (int) config('anaqatuk.phone_length');
        $prefix = (string) config('anaqatuk.phone_prefix');

        if (! preg_match('/^'.$prefix.'\d{'.($length - 1).'}$/', $phone)) {
            throw new RuntimeException('أدخلي رقم جوال صحيح (٩ أرقام تبدأ بـ 5)');
        }

        $this->sms->sendOtp($phone);
    }

    /**
     * Verify the OTP and return the authenticated user + token.
     *
     * @return array{user:User,token:string}
     */
    public function verifyOtp(string $phone, string $code, ?int $regionId = null): array
    {
        if (! $this->sms->verify($phone, $code)) {
            throw new RuntimeException('الكود غير صحيح — تأكدي من الرسالة وحاولي مرة ثانية');
        }

        $user = User::firstOrCreate(
            ['phone' => $phone],
            ['name' => 'مستخدمة أناقتك', 'region_id' => $regionId],
        );

        $user->forceFill([
            'phone_verified_at' => $user->phone_verified_at ?? Carbon::now(),
            'region_id' => $regionId ?? $user->region_id,
        ])->save();

        $token = $user->createToken('mobile')->plainTextToken;

        return ['user' => $user->fresh(), 'token' => $token];
    }
}
