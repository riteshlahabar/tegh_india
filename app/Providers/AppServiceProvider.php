<?php

namespace App\Providers;

use App\Http\Controllers\StorageLinkController;
use App\Support\SmtpSettings;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        require_once app_path('Support/helpers.php');
    }

    public function boot(): void
    {
        date_default_timezone_set(config('app.timezone', 'Asia/Kolkata'));
        Paginator::useBootstrapFive();

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        try {
            $link = public_path('storage');
            if (! is_link($link) && ! file_exists($link)) {
                StorageLinkController::ensureStorageLink();
            }
        } catch (\Throwable $e) {
            // Never break the site if symlink is unavailable
        }

        try {
            SmtpSettings::applyFromDatabase();
        } catch (\Throwable $e) {
            // Keep default mail config if settings table is unavailable
        }
    }
}
