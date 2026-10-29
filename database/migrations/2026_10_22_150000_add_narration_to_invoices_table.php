<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('invoices') && ! Schema::hasColumn('invoices', 'narration')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->text('narration')->nullable()->after('po_no');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('invoices') && Schema::hasColumn('invoices', 'narration')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropColumn('narration');
            });
        }
    }
};
