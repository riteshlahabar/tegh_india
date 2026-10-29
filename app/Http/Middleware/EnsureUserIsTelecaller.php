<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsTelecaller
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        if (! $user || ! $user->isTelecaller()) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Telecaller access only.'], 403);
            }

            abort(403, 'Telecaller access only.');
        }

        return $next($request);
    }
}
