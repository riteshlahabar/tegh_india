<?php

return [
    'base_url' => env('BUSY_API_BASE_URL', 'http://api.apnabazarapp.in/WebAPI/V2'),
    'username' => env('BUSY_API_USERNAME', ''),
    'password' => env('BUSY_API_PASSWORD', ''),
    'timeout' => (int) env('BUSY_API_TIMEOUT', 90),
    'verify_ssl' => env('BUSY_API_VERIFY_SSL', false),
];
