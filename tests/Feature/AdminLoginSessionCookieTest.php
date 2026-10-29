<?php

namespace Tests\Feature;

use Tests\TestCase;

class AdminLoginSessionCookieTest extends TestCase
{
    public function test_login_cookie_is_not_bound_to_previous_server_domain(): void
    {
        config(['session.domain' => '.turnkeyinfotech.live']);

        $response = $this->get('/login');

        $response->assertOk();

        $sessionCookie = collect($response->headers->getCookies())
            ->first(fn ($cookie) => $cookie->getName() === config('session.cookie'));

        $this->assertNotNull($sessionCookie);
        $this->assertTrue(
            in_array($sessionCookie->getDomain(), [null, ''], true),
            'Session cookie must not keep a domain from another server'
        );
    }
}
