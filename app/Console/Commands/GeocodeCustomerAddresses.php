<?php

namespace App\Console\Commands;

use App\Services\GoogleGeocodingService;
use Illuminate\Console\Command;

class GeocodeCustomerAddresses extends Command
{
    protected $signature = 'customers:geocode-addresses {--limit=800 : Max customers to geocode this run}';

    protected $description = 'Geocode customer sheet addresses that have no map coordinates';

    public function handle(GoogleGeocodingService $geocoder): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $done = 0;

        $this->info('Geocoding customer addresses onto the live map…');

        while ($done < $limit) {
            $batch = min(GoogleGeocodingService::BATCH_SIZE, $limit - $done);
            $result = $geocoder->geocodeMissing($batch);

            $done += $result['processed'];

            if ($result['error']) {
                $this->error($result['error']);

                return self::FAILURE;
            }

            $this->line("Placed {$result['geocoded']}, skipped {$result['failed']}, remaining {$result['remaining']}.");

            if ($result['processed'] === 0 || $result['geocoded'] === 0 || $result['remaining'] === 0) {
                break;
            }
        }

        $this->info("Finished. {$done} address(es) processed.");

        return self::SUCCESS;
    }
}
