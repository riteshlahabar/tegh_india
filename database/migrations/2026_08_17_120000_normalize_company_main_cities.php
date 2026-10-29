<?php

use App\Models\City;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        if (! class_exists(City::class)) {
            return;
        }

        City::reclassifyImportedLocations();
    }

    public function down(): void
    {
        // Location repair is not reversible without the original sheet values.
    }
};
