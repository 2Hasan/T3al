<?php

namespace App\Console\Commands;

use App\Actions\SuspendExpiredMerchants;
use Illuminate\Console\Command;

class SuspendExpiredMerchantsCommand extends Command
{
    protected $signature = 'merchants:suspend-expired';

    protected $description = 'Suspend shops whose trial or paid period has ended';

    public function handle(SuspendExpiredMerchants $suspendExpiredMerchants): int
    {
        $count = $suspendExpiredMerchants->handle();

        $this->info("Suspended {$count} shop(s).");

        return self::SUCCESS;
    }
}
