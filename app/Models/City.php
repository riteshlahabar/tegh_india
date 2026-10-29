<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory;

    public const MAIN_NAMES = ['Gujarat', 'Mumbai', 'Pune'];

    /**
     * Excel "City" values that are actually sub-cities, mapped to Branch.
     *
     * @var array<string, string>
     */
    public const SUBCITY_PARENT = [
        'Ahmedabad' => 'Gujarat',
        'Ahmednagar' => 'Mumbai',
        'Ambarnath' => 'Mumbai',
        'Aurangabad' => 'Pune',
        'Badlapur' => 'Mumbai',
        'Bharuch' => 'Gujarat',
        'Bhiwandi' => 'Mumbai',
        'Dombivali' => 'Mumbai',
        'Halol' => 'Gujarat',
        'Kalyan' => 'Mumbai',
        'Karjat' => 'Mumbai',
        'Manor' => 'Mumbai',
        'Mira Road' => 'Mumbai',
        'Naigaon' => 'Mumbai',
        'Nallasopara' => 'Mumbai',
        'Nashik' => 'Mumbai',
        'Navi Mumbai' => 'Mumbai',
        'Navsari' => 'Gujarat',
        'Osmanabad' => 'Mumbai',
        'Palghar' => 'Mumbai',
        'Panvel' => 'Mumbai',
        'Raigad' => 'Mumbai',
        'Shahapur' => 'Mumbai',
        'Silvassa' => 'Mumbai',
        'Sindhudurg' => 'Mumbai',
        'Surat' => 'Gujarat',
        'Talegaon' => 'Pune',
        'Thane' => 'Mumbai',
        'Ulhasnagar' => 'Mumbai',
        'Umbergaon' => 'Gujarat',
        'Valsad' => 'Gujarat',
        'Vapi' => 'Gujarat',
        'Vasai' => 'Mumbai',
        'Virar' => 'Mumbai',
        'Wada' => 'Mumbai',
    ];

    protected $fillable = [
        'name',
    ];

    public function subCities()
    {
        return $this->hasMany(SubCity::class)->orderBy('name');
    }

    public function scopeMain(Builder $query): Builder
    {
        return $query->whereIn('name', self::MAIN_NAMES)->orderBy('name');
    }

    public static function canonicalName(?string $name): ?string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return null;
        }

        foreach (self::MAIN_NAMES as $main) {
            if (strcasecmp($main, $name) === 0) {
                return $main;
            }
        }

        return null;
    }

    public static function isMain(?string $name): bool
    {
        return self::canonicalName($name) !== null;
    }

    public static function parentForSubCity(?string $name): ?string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return null;
        }

        if ($main = self::canonicalName($name)) {
            return $main;
        }

        foreach (self::SUBCITY_PARENT as $sub => $parent) {
            if (strcasecmp($sub, $name) === 0) {
                return $parent;
            }
        }

        return null;
    }

    /**
     * Sheet columns: Branch = main city, City = sub-city.
     *
     * @return array{city: string, sub_city: ?string}
     */
    public static function resolveLocation(?string $branch, ?string $excelCity, ?string $station = null): array
    {
        $branch = trim((string) $branch);
        $excelCity = trim((string) $excelCity);
        $station = trim((string) $station);

        $city = self::canonicalName($branch)
            ?: self::canonicalName($excelCity)
            ?: self::parentForSubCity($excelCity)
            ?: self::parentForSubCity($station)
            ?: self::parentForSubCity($branch)
            ?: '';

        $sub = $station !== '' ? $station : $excelCity;
        if ($sub !== '' && $city !== '' && strcasecmp($sub, $city) === 0) {
            $sub = ($station !== '' && strcasecmp($station, $city) !== 0) ? $station : '';
        }

        return [
            'city' => $city,
            'sub_city' => $sub !== '' ? $sub : null,
        ];
    }

    /**
     * Repair already-imported company rows where City was stored as the main city.
     *
     * @return array{city: string, sub_city: ?string}
     */
    public static function normalizeStored(?string $city, ?string $subCity): array
    {
        $city = trim((string) $city);
        $subCity = trim((string) $subCity);

        if (self::canonicalName($city)) {
            return self::resolveLocation($city, $subCity);
        }

        return self::resolveLocation(null, $city !== '' ? $city : $subCity, $city !== '' ? $subCity : null);
    }

    public static function reclassifyImportedLocations(): void
    {
        foreach (self::MAIN_NAMES as $name) {
            static::query()->firstOrCreate(['name' => $name]);
        }

        Company::query()->each(function (Company $company) {
            $loc = self::normalizeStored($company->city, $company->sub_city);
            if ($loc['city'] === '' && ! filled($company->city)) {
                return;
            }

            $updates = [];
            if ($loc['city'] !== '' && $loc['city'] !== $company->city) {
                $updates['city'] = $loc['city'];
            }
            if (($loc['sub_city'] ?? null) !== $company->sub_city) {
                $updates['sub_city'] = $loc['sub_city'];
            }
            if ($updates) {
                $company->update($updates);
            }

            if ($loc['city'] !== '') {
                $parent = static::query()->where('name', $loc['city'])->first();
                if ($parent && $loc['sub_city']) {
                    SubCity::firstOrCreate([
                        'city_id' => $parent->id,
                        'name' => $loc['sub_city'],
                    ]);
                }
            }
        });

        $extras = static::query()->whereNotIn('name', self::MAIN_NAMES)->get();
        foreach ($extras as $extra) {
            $parentName = self::parentForSubCity($extra->name);
            if (! $parentName) {
                continue;
            }
            $parent = static::query()->where('name', $parentName)->first();
            if (! $parent) {
                continue;
            }

            SubCity::firstOrCreate([
                'city_id' => $parent->id,
                'name' => $extra->name,
            ]);

            SubCity::query()
                ->where('city_id', $extra->id)
                ->get()
                ->each(function (SubCity $sub) use ($parent) {
                    $exists = SubCity::query()
                        ->where('city_id', $parent->id)
                        ->where('name', $sub->name)
                        ->exists();
                    if ($exists) {
                        $sub->delete();
                    } else {
                        $sub->update(['city_id' => $parent->id]);
                    }
                });

            $extra->delete();
        }
    }
}
