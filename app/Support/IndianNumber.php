<?php

namespace App\Support;

class IndianNumber
{
    /**
     * Format numbers in Indian grouping style.
     *
     * Examples:
     * - 641296 => 6,41,296
     * - 10973926.91 => 1,09,73,926.91
     */
    public static function format(float|int|string|null $value, int $decimals = 0): string
    {
        $num = (float) ($value ?? 0);
        $fixed = number_format($num, $decimals, '.', '');

        $parts = explode('.', $fixed, 2);
        $int = $parts[0] ?? '0';
        $dec = $parts[1] ?? '';

        $sign = '';
        if (str_starts_with($int, '-')) {
            $sign = '-';
            $int = substr($int, 1);
        }

        if (strlen($int) <= 3) {
            $grouped = $int;
        } else {
            $last3 = substr($int, -3);
            $rest = substr($int, 0, -3);
            $rest = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest) ?? $rest;
            $grouped = $rest.','.$last3;
        }

        return $decimals > 0
            ? $sign.$grouped.'.'.$dec
            : $sign.$grouped;
    }
}
