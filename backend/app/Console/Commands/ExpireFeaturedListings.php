<?php

namespace App\Console\Commands;

use App\Services\FeaturedService;
use Illuminate\Console\Command;

/** Daily expiry of featured placements past their window. SPEC §6. */
class ExpireFeaturedListings extends Command
{
    protected $signature = 'featured:expire';

    protected $description = 'Expire featured placements whose window has ended';

    public function handle(FeaturedService $featured): int
    {
        $count = $featured->expireDue();
        $this->info("Expired {$count} featured placement(s).");

        return self::SUCCESS;
    }
}
