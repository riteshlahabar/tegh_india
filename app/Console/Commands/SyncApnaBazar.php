<?php

namespace App\Console\Commands;

use App\Services\Apna\ApnaSyncService;
use Illuminate\Console\Command;
use Throwable;

class SyncApnaBazar extends Command
{
    protected $signature = 'apna:sync';

    protected $description = 'Pull Apna Bazar accounts, items, outstanding, vouchers, and pending orders into the CRM';

    public function handle(ApnaSyncService $sync): int
    {
        $this->info('Pulling Apna Bazar data…');

        try {
            $counts = $sync->sync();
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info($sync->summaryMessage($counts));

        return self::SUCCESS;
    }
}
