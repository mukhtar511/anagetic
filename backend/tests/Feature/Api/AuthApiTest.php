<?php

use App\Models\Region;

/* SPEC §3.15 — phone + OTP auth returns a Sanctum token. */

it('requests and verifies an OTP and returns a token + user', function () {
    aRegion(); // ensure a region exists for region capture
    $region = Region::first();

    $this->postJson('/api/auth/otp/request', ['phone' => '512345678'])
        ->assertOk();

    $response = $this->postJson('/api/auth/otp/verify', [
        'phone' => '512345678',
        'code' => '1111', // dev code, accepted outside production
        'region_id' => $region->id,
    ])->assertOk();

    $response->assertJsonStructure(['token', 'user' => ['id', 'phone', 'wallet_balance']]);
    expect($response->json('token'))->toBeString()->not->toBeEmpty();

    // The token authenticates /api/me.
    $token = $response->json('token');
    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/me')
        ->assertOk()
        ->assertJsonPath('phone', '512345678');
});
