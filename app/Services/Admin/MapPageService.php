<?php

namespace App\Services\Admin;

use App\Models\AppSetting;
use App\Models\Company;
use App\Models\User;

class MapPageService
{
    public function data(): array
    {
        $mapsEnabled = AppSetting::googleMapsEnabled();
        $apiKey = config('services.google.maps_api_key');

        $salesmen = User::query()
            ->where('role', 'sales_executive')
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'email',
                'branch',
                'latitude',
                'longitude',
                'current_address',
                'last_location_at',
            ]);

        $customers = Company::query()
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'address',
                'city',
                'sub_city',
                'state',
                'pin_code',
                'latitude',
                'longitude',
                'stage',
                'assigned_exec_id',
            ]);

        $customersOnMap = $customers->filter(fn (Company $c) => $c->latitude && $c->longitude)->values();
        $customersMissingPins = $customers->filter(function (Company $c) {
            if ($c->latitude && $c->longitude) {
                return false;
            }

            return filled($c->address) || filled($c->sub_city) || filled($c->city);
        })->values();

        return [
            'mapsEnabled' => $mapsEnabled,
            'googleMapsApiKey' => $apiKey,
            'salesmen' => $salesmen,
            'mapCustomers' => $customers,
            'salesmenOnMap' => $salesmen->filter(fn (User $u) => $u->latitude && $u->longitude)->values(),
            'customersOnMap' => $customersOnMap,
            'customersMissingPins' => $customersMissingPins,
            'missingPinCount' => $customersMissingPins->count(),
            'initialMapData' => [
                'salesmen' => $salesmen->map(fn (User $u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'branch' => $u->branch,
                    'latitude' => $u->latitude !== null ? (float) $u->latitude : null,
                    'longitude' => $u->longitude !== null ? (float) $u->longitude : null,
                    'address' => $u->current_address,
                    'last_location_at' => optional($u->last_location_at)?->toIso8601String(),
                    'online' => $u->last_location_at && $u->last_location_at->gt(now()->subMinutes(15)),
                ])->values(),
                'customers' => $customers->map(fn (Company $c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'address' => $c->address,
                    'city' => $c->city,
                    'sub_city' => $c->sub_city,
                    'latitude' => $c->latitude !== null ? (float) $c->latitude : null,
                    'longitude' => $c->longitude !== null ? (float) $c->longitude : null,
                    'stage' => $c->stage,
                ])->values(),
            ],
        ];
    }

    public function livePayload(): array
    {
        $data = $this->data();

        return [
            'maps_enabled' => $data['mapsEnabled'],
            'salesmen' => $data['salesmen']->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'branch' => $u->branch,
                'latitude' => $u->latitude !== null ? (float) $u->latitude : null,
                'longitude' => $u->longitude !== null ? (float) $u->longitude : null,
                'address' => $u->current_address,
                'last_location_at' => optional($u->last_location_at)?->toIso8601String(),
                'online' => $u->last_location_at && $u->last_location_at->gt(now()->subMinutes(15)),
            ])->values(),
            'customers' => $data['mapCustomers']->map(fn (Company $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'address' => $c->address,
                'city' => $c->city,
                'sub_city' => $c->sub_city,
                'stage' => $c->stage,
                'latitude' => $c->latitude !== null ? (float) $c->latitude : null,
                'longitude' => $c->longitude !== null ? (float) $c->longitude : null,
                'assigned_exec_id' => $c->assigned_exec_id,
            ])->values(),
            'missing_pin_count' => $data['missingPinCount'],
        ];
    }
}
