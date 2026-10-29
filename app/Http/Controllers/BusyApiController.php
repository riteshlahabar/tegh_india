<?php

namespace App\Http\Controllers;

use App\Services\Busy\BusyApiClient;
use App\Services\Busy\BusyApiException;
use App\Services\Busy\BusyEndpoints;
use App\Support\BusyCompanies;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BusyApiController extends Controller
{
    public function catalog(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => BusyEndpoints::listed(),
        ]);
    }

    public function call(Request $request, string $action, BusyApiClient $client): JsonResponse
    {
        if (! $request->user()?->isAdmin()) {
            abort(403);
        }

        if (! isset(BusyEndpoints::CATALOG[$action])) {
            return response()->json([
                'success' => false,
                'error' => "Unknown BUSY API action [{$action}].",
            ], 404);
        }

        $company = $request->input('company');
        if ($company !== null && $company !== '' && ! BusyCompanies::exists($company)) {
            return response()->json([
                'success' => false,
                'error' => sprintf('Unknown company [%s].', $company),
            ], 422);
        }

        // `company` selects the credentials, so it must not travel on to the API as
        // a parameter -- Apna Bazar has no such field.
        $params = $request->except(['_token', '_method', 'company']);
        $client = $client->forCompany($company ?: null);

        try {
            $payload = $client->call($action, $params);
        } catch (BusyApiException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'action' => $action,
            'company' => $client->company(),
            'data' => $payload,
        ]);
    }
}
