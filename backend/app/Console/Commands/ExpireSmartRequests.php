<?php

namespace App\Console\Commands;

use App\Services\SmartRequestService;
use Illuminate\Console\Command;

/** Expire smart requests past their 24h TTL. SPEC §4.7 / §6. */
class ExpireSmartRequests extends Command
{
    protected $signature = 'smart-requests:expire';

    protected $description = 'Expire smart requests whose TTL has elapsed';

    public function handle(SmartRequestService $service): int
    {
        $count = $service->expireDue();
        $this->info("Expired {$count} smart request(s).");

        return self::SUCCESS;
    }
}
