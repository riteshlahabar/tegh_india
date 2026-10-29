<?php

namespace App\Console\Commands;

use App\Services\Busy\BusyApiException;
use App\Services\Busy\BusySyncService;
use Illuminate\Console\Command;

class SyncBusyDataCommand extends Command
{
    protected $signature = 'busy:sync {--from= : Start date Y-m-d} {--to= : End date Y-m-d}';

    protected $description = 'Pull parties, outstanding, sales vouchers and cancelled bills from BUSY into the CRM';

    public function handle(BusySyncService $sync): int
    {
        $this->info('Syncing from BUSY…');

        try {
            $result = $sync->sync(
                triggeredBy: 'artisan',
                from: $this->option('from') ?: null,
                to: $this->option('to') ?: null,
            );
        } catch (BusyApiException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'BUSY sync complete: %d parties, %d invoices, %d closed.',
            $result['parties_upserted'],
            $result['invoices_upserted'],
            $result['invoices_closed'],
        ));

        return self::SUCCESS;
    }
}
