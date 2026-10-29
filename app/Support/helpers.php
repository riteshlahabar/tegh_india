<?php

use Carbon\CarbonInterface;

if (! function_exists('inr')) {
    function inr(float|int|string|null $amount): string
    {
        $number = (int) round((float) $amount);
        $negative = $number < 0;
        $number = abs($number);
        $num = (string) $number;
        $last3 = substr($num, -3);
        $rest = substr($num, 0, -3);
        if ($rest !== '') {
            $last3 = ','.$last3;
        }
        $formatted = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest).$last3;

        return ($negative ? '-₹' : '₹').$formatted;
    }
}

if (! function_exists('crm_date')) {
    function crm_date(?CarbonInterface $date): string
    {
        return $date ? $date->format('d M Y') : '-';
    }
}

if (! function_exists('crm_status_class')) {
    function crm_status_class(?string $status): string
    {
        return match ($status) {
            'Overdue' => 'badge-overdue',
            'Due Today' => 'badge-due',
            'Paid' => 'badge-paid',
            'Promise Given' => 'badge-promise',
            'Broken Promise' => 'badge-broken',
            'Upcoming' => 'badge-upcoming',
            default => 'badge-connected',
        };
    }
}

if (! function_exists('crm_priority_class')) {
    function crm_priority_class(?string $priority): string
    {
        return match ($priority) {
            'high' => 'p-high',
            'med' => 'p-med',
            default => 'p-low',
        };
    }
}

if (! function_exists('crm_initials')) {
    function crm_initials(?string $name): string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return 'U';
        }
        $parts = preg_split('/\s+/', $name) ?: [];
        if (count($parts) === 1) {
            return strtoupper(substr($parts[0], 0, 2));
        }

        return strtoupper(substr($parts[0], 0, 1).substr($parts[1], 0, 1));
    }
}
