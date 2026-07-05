<?php

namespace Database\Seeders;

use App\Models\Region;
use Illuminate\Database\Seeder;

/**
 * The 12 supported regions — SPEC §3.15 / config('anaqatuk.regions').
 */
class RegionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('anaqatuk.regions') as $name) {
            Region::firstOrCreate(['name' => $name]);
        }
    }
}
