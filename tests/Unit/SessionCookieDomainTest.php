<?php

namespace Tests\Unit;

use App\Support\SessionCookieDomain;
use PHPUnit\Framework\TestCase;

class SessionCookieDomainTest extends TestCase
{
    public function test_keeps_matching_parent_domain(): void
    {
        $this->assertSame(
            '.teghindia.com',
            SessionCookieDomain::resolve('.teghindia.com', 'b2bcrm.teghindia.com')
        );
    }

    public function test_drops_domain_from_a_previous_server(): void
    {
        $this->assertNull(
            SessionCookieDomain::resolve('.turnkeyinfotech.live', 'b2bcrm.teghindia.com')
        );
    }

    public function test_null_or_empty_stays_host_only(): void
    {
        $this->assertNull(SessionCookieDomain::resolve(null, 'b2bcrm.teghindia.com'));
        $this->assertNull(SessionCookieDomain::resolve('', 'b2bcrm.teghindia.com'));
    }
}
