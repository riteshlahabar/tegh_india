<?php

namespace App\Console\Commands;

use App\Services\Busy\BusyApiException;
use App\Services\Busy\BusySyncService;
use App\Support\BusyCompanies;
use Illuminate\Console\Command;

class SyncBusyDataCommand extends Command
{
    protected $signature = 'busy:sync
        {--from= : Start date Y-m-d}
        {--to= : End date Y-m-d}
        {--company= : Sync only this company (mumbai, gujarat, pune). Defaults to every configured company.}';

    protected $description = 'Pull parties, outstanding, sales vouchers and cancelled bills from BUSY into the CRM';

    public function handle(BusySyncService $sync): int
    {
        $only = $this->option('company') ?: null;

        if ($only !== null && ! BusyCompanies::exists($only)) {
            $this->error(sprintf('Unknown company [%s]. Known: %s.', $only, implode(', ', BusyCompanies::slugs())));

            return self::FAILURE;
        }

        $this->info($only === null ? 'Syncing every configured company…' : 'Syncing '.BusyCompanies::label($only).'…');

        try {
            $result = $sync->sync(
                triggeredBy: 'artisan',
                from: $this->option('from') ?: null,
                to: $this->option('to') ?: null,
                company: $only,
            );
        } catch (BusyApiException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->table(
            ['Company', 'Parties', 'Invoices', 'Closed', 'Result'],
            array_map(fn (string $slug, array $row) => [
                BusyCompanies::label($slug),
                $row['parties_upserted'],
                $row['invoices_upserted'],
                $row['invoices_closed'],
                $row['error'] ?? 'ok',
            ], array_keys($result['companies']), $result['companies']),
        );

        $this->info(sprintf(
            'Total: %d parties, %d invoices, %d closed.',
            $result['parties_upserted'],
            $result['invoices_upserted'],
            $result['invoices_closed'],
        ));

        // A company that failed while others succeeded still signals failure, so a
        // cron wrapper notices a stale credential instead of reporting success.
        return $result['errors'] === [] ? self::SUCCESS : self::FAILURE;
    }
}
