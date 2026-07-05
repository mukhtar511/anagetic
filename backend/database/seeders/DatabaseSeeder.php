<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Reproduce the prototype's demo data literally — SPEC §7.
     * Called in dependency order.
     */
    public function run(): void
    {
        $this->call([
            RegionSeeder::class,        // 12 regions — everything references them.
            StoreSeeder::class,         // owner users + stores + buyer نوف.
            ProductSeeder::class,       // products, modes, colors, addons (needs stores).
            CouponSeeder::class,        // platform + store coupons (needs stores).
            WalletLedgerSeeder::class,  // نوف's wallet + ledger (needs نوف).
            NotificationSeeder::class,  // نوف's notifications (needs نوف).
            AdminSeeder::class,         // Filament platform admin.
        ]);
    }
}
