<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * busy_sync_logs is the table BusySyncService actually writes; apna_sync_logs
 * belongs to the dormant Apna integration and was the one covered by
 * 2026_10_29_120000. A sync run now visits each configured company in turn, so a
 * log row has to say which company it describes - otherwise a failure against
 * Gujarat is indistinguishable from one against Mumbai.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('busy_sync_logs')) {
            return;
        }

        if (! Schema::hasColumn('busy_sync_logs', 'company')) {
            Schema::table('busy_sync_logs', function (Blueprint $table) {
                $table->string('company', 32)->nullable()->after('triggered_by');
            });
        }

        // Every run recorded so far used the single Mumbai credential.
        DB::table('busy_sync_logs')
            ->whereNull('company')
            ->update(['company' => (string) config('busy.default_company', 'mumbai')]);

        try {
            Schema::table('busy_sync_logs', function (Blueprint $table) {
                $table->index('company');
            });
        } catch (\Throwable $e) {
            // Index already present.
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('busy_sync_logs') || ! Schema::hasColumn('busy_sync_logs', 'company')) {
            return;
        }

        try {
            Schema::table('busy_sync_logs', function (Blueprint $table) {
                $table->dropIndex(['company']);
            });
        } catch (\Throwable $e) {
            // Index already absent.
        }

        Schema::table('busy_sync_logs', function (Blueprint $table) {
            $table->dropColumn('company');
        });
    }
};
