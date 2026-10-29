<?php

namespace App\Support;

class SessionCookieDomain
{
    /**
     * Browsers reject Set-Cookie if the domain does not match the current host.
     * Drop a leftover SESSION_DOMAIN from a previous server so login can persist.
     */
    public static function resolve(?string $configuredDomain, string $host): ?string
    {
        if ($configuredDomain === null || $configuredDomain === '') {
            return null;
        }

        $normalized = ltrim($configuredDomain, '.');

        if ($host === $normalized || str_ends_with($host, '.'.$normalized)) {
            return $configuredDomain;
        }

        return null;
    }
}
