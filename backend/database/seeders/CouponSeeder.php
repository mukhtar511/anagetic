<?php

namespace Database\Seeders;

use App\Models\Coupon;
use App\Models\Store;
use Illuminate\Database\Seeder;

/**
 * Platform + store coupons from SPEC §7 (الكوبونات).
 */
class CouponSeeder extends Seeder
{
    public function run(): void
    {
        $reem = Store::where('slug', 'reem-couture')->firstOrFail();
        $noura = Store::where('slug', 'dar-noura')->firstOrFail();

        // Platform coupon.
        Coupon::updateOrCreate(
            ['owner' => 'platform', 'store_id' => null, 'code' => 'ANAQA10'],
            [
                'kind' => 'pct',
                'value' => 10,
                'cap' => 50,
                'min' => 100,
                'used_count' => 0,
                'active' => true,
                'note' => 'خصم ١٠٪ حتى ٥٠ ر.س — هدية أناقتك',
            ],
        );

        // Store coupons.
        Coupon::updateOrCreate(
            ['owner' => 'store', 'store_id' => $reem->id, 'code' => 'REEM100'],
            ['kind' => 'fix', 'value' => 100, 'cap' => null, 'min' => 1000, 'used_count' => 0, 'active' => true],
        );

        Coupon::updateOrCreate(
            ['owner' => 'store', 'store_id' => $noura->id, 'code' => 'NOURA50'],
            ['kind' => 'fix', 'value' => 50, 'cap' => null, 'min' => 500, 'used_count' => 14, 'active' => true],
        );

        Coupon::updateOrCreate(
            ['owner' => 'store', 'store_id' => $noura->id, 'code' => 'EID15'],
            ['kind' => 'pct', 'value' => 15, 'cap' => null, 'min' => 400, 'used_count' => 41, 'active' => false],
        );
    }
}
