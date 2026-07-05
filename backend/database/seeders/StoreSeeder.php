<?php

namespace Database\Seeders;

use App\Models\Region;
use App\Models\Store;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;

/**
 * The 10 designer stores from SPEC §7 (each with an owner User) plus the primary
 * buyer نوف that the wallet / notification seeders reference.
 */
class StoreSeeder extends Seeder
{
    /** Shared packaging tiers offered by every store — SPEC §7 (التغليف). */
    public static function packaging(): array
    {
        return [
            ['key' => 'normal', 'name' => 'تغليف عادي', 'price' => 0, 'enabled' => true],
            ['key' => 'simple', 'name' => 'تغليف بسيط', 'price' => 15, 'enabled' => true],
            ['key' => 'special', 'name' => 'تغليف سبيشل', 'price' => 45, 'enabled' => true],
            ['key' => 'gift', 'name' => 'تغليف هدايا', 'price' => 65, 'enabled' => false],
        ];
    }

    public function run(): void
    {
        $riyadh = Region::where('name', 'الرياض')->firstOrFail();

        // Primary buyer — referenced by phone '512345678' in the wallet & notification seeders.
        $nouf = User::updateOrCreate(
            ['phone' => '512345678'],
            [
                'name' => 'نوف',
                'region_id' => $riyadh->id,
                'is_verified_seller' => true,
                'phone_verified_at' => now(),
            ],
        );
        Wallet::firstOrCreate(['user_id' => $nouf->id], ['balance' => 0]);

        // slug, store name, owner name, tagline, rating, completed orders, joined year,
        // status, comm_mode, phone_visible, phone.
        $stores = [
            ['dar-noura', 'دار نورة للعبايات', 'نورة', 'عبايات مناسبات وتفصيل', 4.9, 620, 2023, 'open', 'chat', false, null, 97],
            ['reem-couture', 'ريم كوتور', 'ريم', 'فساتين سهرة — بيع وتأجير', 4.8, 410, 2024, 'open', 'call', true, '0551234567', 0],
            ['ward-aljori', 'ورد الجوري للعناية', 'الجوري', 'خلطات طبيعية وكريمات', 4.9, 890, 2022, 'open', 'chat', false, null, 0],
            ['jawaher-hind', 'جواهر هند', 'هند', 'إكسسوارات ومشغولات يدوية', 4.7, 270, 2024, 'open', 'chat', false, null, 0],
            ['lamsa-latifa', 'لمسة لطيفة', 'لطيفة', 'فساتين أطفال ومواليد', 4.8, 150, 2025, 'open', 'chat', false, null, 0],
            ['khazanat-sara', 'خزانة سارة', 'سارة', 'فساتين مستعملة — بيع وتأجير', 4.6, 95, 2025, 'paused', 'payfirst', false, null, 0],
            ['atr-woud', 'عطر وعود', 'وعود', 'خلطات عطرية وعناية بالشعر', 4.9, 340, 2024, 'open', 'chat', false, null, 0],
            ['makhzoun-mounira', 'مخزون منيرة', 'منيرة', 'عبايات بدون براند — دفعات', 4.5, 210, 2024, 'closed', 'chat', false, null, 0],
            ['khazanat-anoud', 'خزانة العنود', 'العنود', 'مستعمل موثّق', 4.8, 0, null, 'open', 'chat', false, null, 0],
            ['makhzoun-musammimat', 'مخزون مصممات', 'مصممات', 'بدون براند', 4.5, 0, null, 'open', 'chat', false, null, 0],
        ];

        $phone = 550000001;
        foreach ($stores as [$slug, $storeName, $ownerName, $tagline, $rating, $completed, $year, $status, $comm, $phoneVisible, $storePhone, $onTime]) {
            $owner = User::updateOrCreate(
                ['phone' => (string) $phone],
                [
                    'name' => $ownerName,
                    'region_id' => $riyadh->id,
                    'is_verified_seller' => true,
                    'phone_verified_at' => now(),
                ],
            );
            $phone++;

            Store::updateOrCreate(
                ['slug' => $slug],
                [
                    'user_id' => $owner->id,
                    'name' => $storeName,
                    'tagline' => $tagline,
                    'region_id' => $riyadh->id,
                    'status' => $status,
                    'comm_mode' => $comm,
                    'phone_visible' => $phoneVisible,
                    'phone' => $storePhone,
                    'packaging' => self::packaging(),
                    'verification_status' => 'verified',
                    'joined_year' => $year,
                    'rating_avg' => $rating,
                    'completed_orders' => $completed,
                    'on_time_rate' => $onTime,
                ],
            );
        }
    }
}
