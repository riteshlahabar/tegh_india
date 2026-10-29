<?php

namespace Tests\Unit;

use App\Models\City;
use Tests\TestCase;

class CityLocationTest extends TestCase
{
    public function test_branch_is_the_main_city_and_sheet_city_is_sub_city(): void
    {
        $this->assertSame(
            ['city' => 'Mumbai', 'sub_city' => 'Vasai'],
            City::resolveLocation('Mumbai', 'Vasai')
        );
        $this->assertSame(
            ['city' => 'Gujarat', 'sub_city' => 'Surat'],
            City::resolveLocation('Gujarat', 'Surat')
        );
        $this->assertSame(
            ['city' => 'Pune', 'sub_city' => 'Talegaon'],
            City::resolveLocation('Pune', 'Talegaon')
        );
    }

    public function test_same_branch_and_city_does_not_duplicate_sub_city(): void
    {
        $this->assertSame(
            ['city' => 'Mumbai', 'sub_city' => null],
            City::resolveLocation('Mumbai', 'Mumbai')
        );
    }

    public function test_silvassa_maps_under_mumbai_when_branch_missing(): void
    {
        $this->assertSame(
            ['city' => 'Mumbai', 'sub_city' => 'Silvassa'],
            City::resolveLocation('', 'Silvassa')
        );
    }

    public function test_stored_subcity_values_normalize_to_main_city(): void
    {
        $this->assertSame(
            ['city' => 'Mumbai', 'sub_city' => 'Kalyan'],
            City::normalizeStored('Kalyan', null)
        );
        $this->assertSame(
            ['city' => 'Mumbai', 'sub_city' => 'Vasai'],
            City::normalizeStored('Mumbai', 'Vasai')
        );
    }
}
