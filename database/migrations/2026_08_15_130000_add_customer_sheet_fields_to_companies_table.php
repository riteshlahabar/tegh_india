<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (! Schema::hasColumn('companies', 'alternate_mobile')) {
                $table->string('alternate_mobile', 30)->nullable()->after('mobile_number');
            }
            if (! Schema::hasColumn('companies', 'state')) {
                $table->string('state', 100)->nullable()->after('city');
            }
            if (! Schema::hasColumn('companies', 'pin_code')) {
                $table->string('pin_code', 20)->nullable()->after('state');
            }
            if (! Schema::hasColumn('companies', 'brand')) {
                $table->string('brand', 150)->nullable()->after('industry');
            }
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            foreach (['alternate_mobile', 'state', 'pin_code', 'brand'] as $col) {
                if (Schema::hasColumn('companies', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
