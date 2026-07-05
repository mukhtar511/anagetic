<?php

namespace App\Console\Commands;

use App\Models\NotificationCenter;
use App\Services\FeaturedService;
use Illuminate\Console\Command;

/** "⭐ إعلانك المميز ينتهي غدًا" — 24h expiry notice. SPEC §4.4 / §6. */
class NotifyExpiringFeatured extends Command
{
    protected $signature = 'featured:notify-expiring';

    protected $description = 'Notify sellers whose featured placement expires within 24h';

    public function handle(FeaturedService $featured): int
    {
        $due = $featured->dueForExpiryNotice();

        foreach ($due as $listing) {
            $seller = $listing->product->store->user;
            NotificationCenter::create([
                'user_id' => $seller->id,
                'ic' => '⭐',
                'title' => 'إعلانك المميز ينتهي غدًا',
                'body' => "«{$listing->product->title}» — جددي الباقة عشان يبقى بصدارة المميزة",
                'go' => 'sellerPage',
            ]);
            $listing->update(['expiring_notified' => true]);
        }

        $this->info("Notified {$due->count()} seller(s).");

        return self::SUCCESS;
    }
}
