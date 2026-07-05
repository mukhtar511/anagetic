<?php

namespace App\Http\Controllers\Api;

use App\Services\AuthService;
use App\Services\WalletService;
use Illuminate\Http\Request;

class AuthController extends ApiController
{
    public function __construct(
        private readonly AuthService $auth,
        private readonly WalletService $wallet,
    ) {}

    /** POST /api/auth/otp/request */
    public function requestOtp(Request $request)
    {
        $data = $request->validate(['phone' => ['required', 'string']]);

        $this->auth->requestOtp($data['phone']);

        return $this->ok(['message' => 'تم إرسال الكود']);
    }

    /** POST /api/auth/otp/verify */
    public function verifyOtp(Request $request)
    {
        $data = $request->validate([
            'phone' => ['required', 'string'],
            'code' => ['required', 'string'],
            'region_id' => ['nullable', 'integer', 'exists:regions,id'],
        ]);

        $result = $this->auth->verifyOtp($data['phone'], $data['code'], $data['region_id'] ?? null);

        return $this->ok([
            'token' => $result['token'],
            'user' => $this->userPayload($result['user']),
        ]);
    }

    /** POST /api/auth/logout */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->ok(['message' => 'تم تسجيل الخروج']);
    }

    /** GET /api/me */
    public function me(Request $request)
    {
        return $this->ok($this->userPayload($request->user()));
    }

    private function userPayload($user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'phone' => $user->phone,
            'region_id' => $user->region_id,
            'is_verified_seller' => (bool) $user->is_verified_seller,
            'has_store' => $user->store()->exists(),
            'wallet_balance' => $this->wallet->balance($user),
        ];
    }
}
