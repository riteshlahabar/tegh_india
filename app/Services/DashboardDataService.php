<?php

namespace App\Services;

use App\Services\Admin\CommandCentreService;

/**
 * @deprecated Prefer App\Services\Admin\* page services — kept for any legacy callers.
 */
class DashboardDataService
{
    public function forAdminDashboard(): array
    {
        return app(CommandCentreService::class)->data();
    }
}
