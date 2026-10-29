<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('parties', 'mobile')) {
            return;
        }

        Schema::table('parties', function (Blueprint $table) {
            $table->string('mobile', 255)->nullable()->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('parties', 'mobile')) {
            return;
        }

        Schema::table('parties', function (Blueprint $table) {
            $table->string('mobile', 20)->nullable()->change();
        });
    }
};
