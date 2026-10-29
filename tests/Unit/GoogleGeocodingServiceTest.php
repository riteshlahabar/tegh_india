<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Services\GoogleGeocodingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleGeocodingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_query_includes_address_sub_city_and_india(): void
    {
        $company = new Company([
            'address' => 'Shop No 5, Gharat Estate Near HP Petrol, Palghar',
            'sub_city' => 'Virar',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'pin_code' => '401303',
        ]);

        $query = app(GoogleGeocodingService::class)->queryFor($company);

        $this->assertNotNull($query);
        $this->assertStringContainsString('Shop No 5', $query);
        $this->assertStringContainsString('Virar', $query);
        $this->assertStringContainsString('401303', $query);
        $this->assertStringContainsString('India', $query);
    }

    public function test_query_skips_parts_already_in_the_address(): void
    {
        $company = new Company([
            'address' => '15, K C PASTE COMPLEX, JAI SHREE NAGAR, Maharashtra, 421312',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'pin_code' => '421312',
        ]);

        $query = app(GoogleGeocodingService::class)->queryFor($company);

        $this->assertSame(1, substr_count($query, 'Maharashtra'));
        $this->assertSame(1, substr_count($query, '421312'));
    }

    public function test_geocode_company_saves_coordinates_from_google(): void
    {
        Http::fake([
            'maps.googleapis.com/*' => Http::response([
                'status' => 'OK',
                'results' => [[
                    'formatted_address' => 'Virar, Maharashtra, India',
                    'geometry' => [
                        'location' => ['lat' => 19.4550, 'lng' => 72.8111],
                    ],
                ]],
            ], 200),
        ]);

        $company = Company::create([
            'name' => 'Sheet Import Customer',
            'industry' => 'Electrical',
            'city' => 'Mumbai',
            'sub_city' => 'Virar',
            'address' => 'Shop No 5, Palghar',
            'credit_limit' => 0,
            'outstanding_amount' => 0,
            'stage' => 'New',
        ]);

        $ok = app(GoogleGeocodingService::class)->geocodeCompany($company);

        $this->assertTrue($ok);
        $company->refresh();
        $this->assertEqualsWithDelta(19.4550, (float) $company->latitude, 0.0001);
        $this->assertEqualsWithDelta(72.8111, (float) $company->longitude, 0.0001);
    }
}
