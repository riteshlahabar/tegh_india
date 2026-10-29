<?php

namespace App\Http\Controllers;

use App\Services\Busy\BusyApiClient;
use App\Services\Busy\BusyApiException;
use App\Services\Busy\BusyEndpoints;
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

        try {
            $payload = $client->call($action, $request->except(['_token', '_method']));
        } catch (BusyApiException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'action' => $action,
            'data' => $payload,
        ]);
    }
}
