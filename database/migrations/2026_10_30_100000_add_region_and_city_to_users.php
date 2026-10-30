<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gives a telecaller account the two fields every list is scoped by.
 *
 * `region` is a BusyCompanies slug -- the company whose books the rows come
 * from, the same value `invoices.company` holds. `city` is the customer's own
 * town, as BusyPayload::city() derives it from the address lines.
 *
 * `branch` is deliberately left in place: reports.blade.php still renders a
 * Branch dropdown off it. It stays dead until that page stops asking.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'region')) {
                $table->string('region')->nullable()->after('branch');
            }
            if (! Schema::hasColumn('users', 'city')) {
                $table->string('city')->nullable()->after('region');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['region', 'city'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
