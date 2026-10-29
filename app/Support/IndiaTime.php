<?php

namespace App\Support;

use Carbon\Carbon;
use DateTimeInterface;

class IndiaTime
{
    public const TZ = 'Asia/Kolkata';

    public static function now(): Carbon
    {
        return Carbon::now(self::TZ);
    }

    public static function parse(?string $value): ?Carbon
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $raw = trim($value);
        if (strlen($raw) <= 10) {
            return Carbon::parse($raw, self::TZ)->setTime(12, 0)->utc();
        }

        return Carbon::parse($raw, self::TZ)->utc();
    }

    public static function format(?DateTimeInterface $dt, string $pattern = 'd M Y, h:i A'): ?string
    {
        if (! $dt) {
            return null;
        }

        return Carbon::parse($dt->format('Y-m-d H:i:s'), 'UTC')
            ->timezone(self::TZ)
            ->format($pattern);
    }

    public static function iso(?DateTimeInterface $dt): ?string
    {
        if (! $dt) {
            return null;
        }

        return Carbon::parse($dt->format('Y-m-d H:i:s'), 'UTC')
            ->timezone(self::TZ)
            ->toIso8601String();
    }

    public static function date(?DateTimeInterface $dt): ?string
    {
        return self::format($dt, 'Y-m-d');
    }

    public static function todayUtcRange(): array
    {
        $start = Carbon::now(self::TZ)->startOfDay()->utc();
        $end = Carbon::now(self::TZ)->endOfDay()->utc();

        return [$start, $end];
    }
}
