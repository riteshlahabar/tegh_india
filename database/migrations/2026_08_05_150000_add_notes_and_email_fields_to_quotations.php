<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            if (! Schema::hasColumn('quotations', 'notes')) {
                $table->text('notes')->nullable()->after('date');
            }
            if (! Schema::hasColumn('quotations', 'valid_until')) {
                $table->date('valid_until')->nullable()->after('notes');
            }
            if (! Schema::hasColumn('quotations', 'emailed_at')) {
                $table->timestamp('emailed_at')->nullable()->after('valid_until');
            }
            if (! Schema::hasColumn('quotations', 'emailed_to')) {
                $table->string('emailed_to')->nullable()->after('emailed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            foreach (['notes', 'valid_until', 'emailed_at', 'emailed_to'] as $col) {
                if (Schema::hasColumn('quotations', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
