<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MapConfigApiTest extends TestCase
{
    use RefreshDatabase;

    private function salesUser(): User
    {
        return User::factory()->create([
            'role' => 'sales_executive',
            'branch' => 'Mumbai',
        ]);
    }

    public function test_map_config_reports_enabled_as_json_boolean_true(): void
    {
        AppSetting::setValue('google_maps_enabled', true);
        $user = $this->salesUser();
        $token = $user->createToken('android')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/sales/map-config');

        $response->assertOk();
        $response->assertJsonPath('enabled', true);
        $this->assertTrue($response->json('enabled') === true);
    }

    public function test_map_config_treats_on_as_enabled(): void
    {
        AppSetting::query()->updateOrCreate(
            ['key' => 'google_maps_enabled'],
            ['value' => 'on']
        );
        $user = $this->salesUser();
        $token = $user->createToken('android')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/sales/map-config')
            ->assertOk()
            ->assertJsonPath('enabled', true);
    }

    public function test_location_update_is_allowed_when_maps_are_on(): void
    {
        AppSetting::setValue('google_maps_enabled', true);
        $user = $this->salesUser();
        $token = $user->createToken('android')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/sales/location', [
                'latitude' => 19.076,
                'longitude' => 72.8777,
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Location updated');
    }

    public function test_location_update_is_blocked_when_maps_are_off(): void
    {
        AppSetting::setValue('google_maps_enabled', false);
        $user = $this->salesUser();
        $token = $user->createToken('android')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/sales/location', [
                'latitude' => 19.076,
                'longitude' => 72.8777,
            ])
            ->assertForbidden()
            ->assertJsonPath('message', 'Google Maps is disabled by admin.');
    }

    public function test_map_data_includes_customers_from_every_city(): void
    {
        AppSetting::setValue('google_maps_enabled', true);
        $user = $this->salesUser();

        Company::create([
            'name' => 'Mumbai Pin',
            'industry' => 'Electrical',
            'city' => 'Mumbai',
            'address' => 'Andheri',
            'latitude' => 19.076,
            'longitude' => 72.8777,
            'credit_limit' => 0,
            'outstanding_amount' => 0,
            'stage' => 'New',
        ]);
        Company::create([
            'name' => 'Gujarat Pin',
            'industry' => 'Electrical',
            'city' => 'Gujarat',
            'address' => 'Surat',
            'latitude' => 21.1702,
            'longitude' => 72.8311,
            'credit_limit' => 0,
            'outstanding_amount' => 0,
            'stage' => 'New',
        ]);

        $token = $user->createToken('android')->plainTextToken;
        $response = $this->withToken($token)->getJson('/api/sales/map-data');

        $response->assertOk();
        $response->assertJsonFragment(['name' => 'Mumbai Pin']);
        $response->assertJsonFragment(['name' => 'Gujarat Pin']);
    }
}
