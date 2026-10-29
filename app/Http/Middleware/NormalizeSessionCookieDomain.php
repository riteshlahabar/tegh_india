<?php

namespace App\Http\Middleware;

use App\Support\SessionCookieDomain;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeSessionCookieDomain
{
    public function handle(Request $request, Closure $next): Response
    {
        config([
            'session.domain' => SessionCookieDomain::resolve(
                config('session.domain'),
                $request->getHost()
            ),
        ]);

        return $next($request);
    }
}
