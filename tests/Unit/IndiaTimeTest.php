<?php

namespace Tests\Unit;

use App\Support\IndiaTime;
use Carbon\Carbon;
use Tests\TestCase;

class IndiaTimeTest extends TestCase
{
    public function test_naive_datetime_is_treated_as_india_time_and_stored_utc(): void
    {
        $utc = IndiaTime::parse('2026-08-25 14:00:00');

        $this->assertSame('2026-08-25 08:30:00', $utc->format('Y-m-d H:i:s'));
    }

    public function test_utc_instant_formats_in_india_time(): void
    {
        $dt = Carbon::parse('2026-08-25 08:30:00', 'UTC');

        $this->assertSame('25 Aug 2026, 02:00 PM', IndiaTime::format($dt));
    }
}
