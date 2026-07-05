<?php

namespace Database\Seeders;

use App\Models\Region;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/** A platform admin for the Filament panel. SPEC §7. */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@anaqatuk.sa'],
            [
                'name' => 'مشرف أناقتك',
                'phone' => '500000000',
                'region_id' => Region::where('name', 'الرياض')->value('id'),
                'is_admin' => true,
                'is_verified_seller' => false,
                'password' => Hash::make('password'),
                'phone_verified_at' => now(),
            ],
        );
    }
}
