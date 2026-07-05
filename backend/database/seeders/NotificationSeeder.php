<?php

namespace Database\Seeders;

use App\Models\NotificationCenter;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * نوف's notification center — SPEC §7 (الإشعارات) / §3.12.
 * First three are read; the last two (مميز ينتهي غدًا / إرجاع اكتمل) are unread.
 */
class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $nouf = User::where('phone', '512345678')->firstOrFail();

        $nouf->notificationsCenter()->delete();

        // [ic, title, body, go, read].
        $rows = [
            ['🪄', 'عرض جديد على طلبك الذكي', 'دار نورة قدّمت عرضًا بسعر ٧٩٠ ر.س على طلبك الذكي', 'smartReqPage', true],
            ['🛡️', 'استرداد تأمين', 'رجع تأمينك ٥٠٠ ر.س كاملًا لمحفظتك — طلب #A-1027', 'buyerDash', true],
            ['🚚', 'شحنتك مع المندوب', 'شحنتك #A-1043-B صارت مع المندوب — جهّزي كود التسليم', 'ordersPage', true],
            ['⭐', 'إعلانك المميز ينتهي غدًا', 'إعلانك المميز راح ينتهي غدًا — جدّديه عشان يظل بأعلى الصفحة', 'sellerPage', false],
            ['↩️', 'اكتمل إرجاعك', 'اكتمل إرجاع طلبك ورجع لك ٣١٠ ر.س لمحفظتك', 'buyerDash', false],
        ];

        $ts = now();
        foreach ($rows as $i => [$ic, $title, $body, $go, $read]) {
            NotificationCenter::create([
                'user_id' => $nouf->id,
                'ic' => $ic,
                'title' => $title,
                'body' => $body,
                'go' => $go,
                'read_at' => $read ? $ts->copy()->subMinutes($i) : null,
                'created_at' => $ts->copy()->subMinutes($i),
                'updated_at' => $ts->copy()->subMinutes($i),
            ]);
        }
    }
}
