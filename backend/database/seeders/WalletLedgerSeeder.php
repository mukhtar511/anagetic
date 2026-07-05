<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletEntry;
use Illuminate\Database\Seeder;

/**
 * Buyer نوف's unified-wallet ledger — SPEC §7 (كشف المحفظة), newest-first.
 * The wallet balance equals the sum of these signed rows = 434.40 ر.س.
 */
class WalletLedgerSeeder extends Seeder
{
    public function run(): void
    {
        $nouf = User::where('phone', '512345678')->firstOrFail();

        $wallet = Wallet::updateOrCreate(
            ['user_id' => $nouf->id],
            [
                'balance' => 434.40,
                'iban' => 'SA•• ••41 2035',
                'bank_name' => 'مصرف الراجحي',
            ],
        );

        // Wipe any previous ledger so re-seeding stays literal.
        $wallet->entries()->delete();

        // Newest-first: [type, ref, text, amount].
        $rows = [
            ['sale_income', 'A-1039', '💰 دخل بيع — طلب #A-1039 عباية مطرزة (بعد عمولة ١٢٪)', 1152.80],
            ['deposit_refund', 'A-1027', 'استرداد تأمين كامل — عباية زفّة #A-1027', 500.00],
            ['rental_income', 'A-1031', '💰 دخل تأجير — طلب #A-1031 (بعد عمولة ١٢٪)', 281.60],
            ['deposit_refund', 'A-0962', 'استرداد جزئي لتأمين #A-0962 (خُصم ٥٠ — بقعة)', 200.00],
            ['refund', 'A-0940', 'استرداد إلغاء طلب #A-0940 قبل الشحن', 160.00],
            ['withdrawal', null, '⬇ سحب لحسابك البنكي — مصرف الراجحي', -1500.00],
            ['payment', 'A-1043-B', 'دفعتِ من المحفظة — جزء من طلب #A-1043-B', -360.00],
        ];

        $ts = now();
        foreach ($rows as $i => [$type, $ref, $text, $amount]) {
            WalletEntry::create([
                'wallet_id' => $wallet->id,
                'type' => $type,
                'ref' => $ref,
                'text' => $text,
                'amount' => $amount,
                'created_at' => $ts->copy()->subMinutes($i),
                'updated_at' => $ts->copy()->subMinutes($i),
            ]);
        }
    }
}
