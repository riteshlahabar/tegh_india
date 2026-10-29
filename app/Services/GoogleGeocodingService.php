<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleGeocodingService
{
    public const BATCH_SIZE = 12;

    public function queryFor(Company $company, bool $includeStreet = true): ?string
    {
        $parts = [];

        if ($includeStreet) {
            $this->pushUnique($parts, $company->address);
        }

        $this->pushUnique($parts, $company->sub_city);
        $this->pushUnique($parts, $company->city);
        $this->pushUnique($parts, $company->state);
        $this->pushUnique($parts, $company->pin_code);

        if ($parts === []) {
            return null;
        }

        $query = implode(', ', $parts);
        if (! str_contains(mb_strtolower($query), 'india')) {
            $query .= ', India';
        }

        return $query;
    }

    /**
     * @return list<string>
     */
    public function queriesFor(Company $company): array
    {
        return array_values(array_unique(array_filter([
            $this->queryFor($company, true),
            $this->queryFor($company, false),
        ])));
    }

    /**
     * @return array{latitude: float, longitude: float, formatted: string}|null
     */
    public function geocode(string $query): ?array
    {
        $key = (string) config('services.google.maps_api_key');
        if ($key === '' || trim($query) === '') {
            return null;
        }

        $response = Http::timeout(12)->get('https://maps.googleapis.com/maps/api/geocode/json', [
            'address' => $query,
            'key' => $key,
            'region' => 'in',
            'language' => 'en',
        ]);

        if (! $response->ok()) {
            Log::warning('Google geocode HTTP error', [
                'status' => $response->status(),
                'query' => $query,
            ]);

            return null;
        }

        $payload = $response->json();
        $status = (string) ($payload['status'] ?? '');

        if ($status === 'REQUEST_DENIED' || $status === 'OVER_QUERY_LIMIT') {
            throw new \RuntimeException(
                (string) ($payload['error_message'] ?? 'Google Geocoding API '.$status)
            );
        }

        if ($status !== 'OK' || empty($payload['results'][0]['geometry']['location'])) {
            return null;
        }

        $location = $payload['results'][0]['geometry']['location'];

        return [
            'latitude' => (float) $location['lat'],
            'longitude' => (float) $location['lng'],
            'formatted' => (string) ($payload['results'][0]['formatted_address'] ?? $query),
        ];
    }

    public function geocodeCompany(Company $company): bool
    {
        foreach ($this->queriesFor($company) as $query) {
            $hit = $this->geocode($query);
            if ($hit === null) {
                continue;
            }

            $company->update([
                'latitude' => $hit['latitude'],
                'longitude' => $hit['longitude'],
            ]);

            return true;
        }

        return false;
    }

    public function missingCoordinatesQuery(): Builder
    {
        return Company::query()
            ->where(function (Builder $q) {
                $q->whereNull('latitude')
                    ->orWhereNull('longitude');
            })
            ->where(function (Builder $q) {
                $q->where(function (Builder $inner) {
                    $inner->whereNotNull('address')->where('address', '!=', '');
                })->orWhere(function (Builder $inner) {
                    $inner->whereNotNull('sub_city')->where('sub_city', '!=', '');
                })->orWhere(function (Builder $inner) {
                    $inner->whereNotNull('city')->where('city', '!=', '');
                });
            })
            ->orderBy('id');
    }

    /**
     * @return array{processed:int, geocoded:int, failed:int, remaining:int, error:?string, placed:array<int, array{id:int, name:string, latitude:float, longitude:float}>}
     */
    public function geocodeMissing(int $limit = self::BATCH_SIZE): array
    {
        $limit = max(1, min(25, $limit));
        $companies = $this->missingCoordinatesQuery()
            ->limit($limit)
            ->get([
                'id',
                'name',
                'address',
                'sub_city',
                'city',
                'state',
                'pin_code',
                'latitude',
                'longitude',
            ]);

        $geocoded = 0;
        $failed = 0;
        $placed = [];
        $error = null;

        foreach ($companies as $company) {
            try {
                if ($this->geocodeCompany($company)) {
                    $company->refresh();
                    $geocoded++;
                    $placed[] = [
                        'id' => $company->id,
                        'name' => $company->name,
                        'latitude' => (float) $company->latitude,
                        'longitude' => (float) $company->longitude,
                    ];
                } else {
                    $failed++;
                }
            } catch (\RuntimeException $e) {
                $error = $e->getMessage();
                break;
            }

            if (! app()->runningUnitTests()) {
                usleep(80_000);
            }
        }

        return [
            'processed' => $geocoded + $failed,
            'geocoded' => $geocoded,
            'failed' => $failed,
            'remaining' => $this->missingCoordinatesQuery()->count(),
            'error' => $error,
            'placed' => $placed,
        ];
    }

    /**
     * @param  list<string>  $parts
     */
    private function pushUnique(array &$parts, mixed $value): void
    {
        $value = trim((string) $value);
        if ($value === '') {
            return;
        }

        $needle = mb_strtolower($value);
        foreach ($parts as $part) {
            if (str_contains(mb_strtolower($part), $needle) || str_contains($needle, mb_strtolower($part))) {
                return;
            }
        }

        $parts[] = $value;
    }
}
