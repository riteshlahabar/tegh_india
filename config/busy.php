<?php

use App\Support\BusyCompanies;

return [
    /*
     * Shared by every company -- the Apna Bazar API distinguishes companies by the
     * login, not by the host or any company-code parameter.
     */
    'base_url' => env('BUSY_API_BASE_URL', 'http://api.apnabazarapp.in/WebAPI/V2'),

    'timeout' => (int) env('BUSY_API_TIMEOUT', 90),

    'verify_ssl' => env('BUSY_API_VERIFY_SSL', false),

    /*
     * Used when no company is named -- the admin API page, a sync without --company,
     * and the slug backfilled onto rows that predate multi-company support.
     */
    'default_company' => env('BUSY_DEFAULT_COMPANY', BusyCompanies::MUMBAI),

    /*
     * One Apna Bazar login per company. For each of the three the username and the
     * password are the same mobile number.
     *
     * These are only fallbacks: App\Support\BusyCompanies reads app_settings first
     * (busy_{slug}_username / busy_{slug}_password) so credentials can be changed
     * from the admin settings page without a deploy. Mumbai additionally falls back
     * to the legacy busy_username / busy_password keys, which is where its
     * credentials are stored today.
     */
    'companies' => [
        BusyCompanies::MUMBAI => [
            'label' => 'Mumbai',
            'username' => env('BUSY_MUMBAI_USERNAME', env('BUSY_API_USERNAME', '')),
            'password' => env('BUSY_MUMBAI_PASSWORD', env('BUSY_API_PASSWORD', '')),
            'invoice_prefixes' => ['TCIPL/'],
        ],
        BusyCompanies::GUJARAT => [
            'label' => 'Gujarat',
            'username' => env('BUSY_GUJARAT_USERNAME', ''),
            'password' => env('BUSY_GUJARAT_PASSWORD', ''),
            'invoice_prefixes' => ['TCIG/'],
        ],
        BusyCompanies::PUNE => [
            'label' => 'Pune',
            'username' => env('BUSY_PUNE_USERNAME', ''),
            'password' => env('BUSY_PUNE_PASSWORD', ''),
            'invoice_prefixes' => ['TCIPLP'],
        ],
    ],

    /*
     * Legacy single-credential keys. Still read so an existing .env keeps working;
     * prefer the per-company entries above.
     */
    'username' => env('BUSY_API_USERNAME', ''),
    'password' => env('BUSY_API_PASSWORD', ''),
];
