<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MapGeocodeTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'branch' => 'All',
        ]);
    }

    private function company(array $overrides = []): Company
    {
        return Company::create(array_merge([
            'name' => 'Sheet Customer Ltd',
            'industry' => 'Electrical',
            'city' => 'Mumbai',
            'sub_city' => 'Virar',
            'address' => 'Shop No 5, Palghar Maharashtra -401303',
            'credit_limit' => 0,
            'outstanding_amount' => 0,
            'stage' => 'New',
        ], $overrides));
    }

    public function test_maps_page_lists_imported_addresses_that_have_no_pin(): void
    {
        AppSetting::setValue('google_maps_enabled', true);
        $admin = $this->admin();
        $this->company();

        $response = $this->actingAs($admin)->get(route('admin.maps'));

        $response->assertOk();
        $response->assertSee('Sheet Customer Ltd', false);
        $response->assertSee('Shop No 5, Palghar Maharashtra -401303', false);
        $response->assertSee('Place addresses on map', false);
        $response->assertSee('id="stat-missing-pins"', false);
    }

    public function test_ungeocoded_endpoint_returns_sheet_address_query(): void
    {
        $admin = $this->admin();
        $company = $this->company();
        $this->company([
            'name' => 'Already Pinned',
            'latitude' => 19.07,
            'longitude' => 72.87,
        ]);

        $response = $this->actingAs($admin)->getJson(route('admin.maps.ungeocoded'));

        $response->assertOk();
        $response->assertJsonPath('remaining', 1);
        $response->assertJsonPath('customers.0.id', $company->id);
        $response->assertJsonPath('customers.0.name', 'Sheet Customer Ltd');
        $this->assertStringContainsString('Palghar', $response->json('customers.0.query'));
    }

    public function test_geocode_missing_saves_coordinates(): void
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

        $admin = $this->admin();
        $company = $this->company();

        $response = $this->actingAs($admin)->postJson(route('admin.maps.geocode-missing'), [
            'limit' => 5,
        ]);

        $response->assertOk();
        $response->assertJsonPath('geocoded', 1);
        $response->assertJsonPath('remaining', 0);

        $company->refresh();
        $this->assertEqualsWithDelta(19.4550, (float) $company->latitude, 0.0001);
        $this->assertEqualsWithDelta(72.8111, (float) $company->longitude, 0.0001);
    }

    public function test_batch_save_writes_map_coordinates(): void
    {
        $admin = $this->admin();
        $company = $this->company();

        $response = $this->actingAs($admin)->postJson(route('admin.maps.locations.batch'), [
            'companies' => [[
                'id' => $company->id,
                'latitude' => 19.2183,
                'longitude' => 72.9781,
            ]],
        ]);

        $response->assertOk();
        $response->assertJsonPath('saved', 1);

        $company->refresh();
        $this->assertEqualsWithDelta(19.2183, (float) $company->latitude, 0.0001);
        $this->assertEqualsWithDelta(72.9781, (float) $company->longitude, 0.0001);
    }

    public function test_live_map_payload_includes_address_and_missing_pin_count(): void
    {
        AppSetting::setValue('google_maps_enabled', true);
        $admin = $this->admin();
        $this->company([
            'latitude' => 19.07,
            'longitude' => 72.87,
        ]);
        $this->company([
            'name' => 'No Pin Yet',
            'address' => 'Vasai East',
        ]);

        $response = $this->actingAs($admin)->getJson(route('admin.maps.live'));

        $response->assertOk();
        $response->assertJsonPath('missing_pin_count', 1);
        $response->assertJsonFragment(['address' => 'Shop No 5, Palghar Maharashtra -401303']);
        $response->assertJsonFragment(['sub_city' => 'Virar']);
    }
}
