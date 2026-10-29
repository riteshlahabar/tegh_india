<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parties', function (Blueprint $table) {
            if (! Schema::hasColumn('parties', 'gstin')) {
                $table->string('gstin')->nullable()->after('email');
            }
        });

        try {
            Schema::table('parties', function (Blueprint $table) {
                $table->index('busy_party_id');
            });
        } catch (\Throwable $e) {}

        try {
            Schema::table('invoices', function (Blueprint $table) {
                $table->index('busy_invoice_id');
            });
        } catch (\Throwable $e) {}

        if (! Schema::hasTable('busy_sync_logs')) {
            Schema::create('busy_sync_logs', function (Blueprint $table) {
                $table->id();
                $table->string('triggered_by')->nullable();
                $table->string('status')->default('running');
                $table->unsignedInteger('parties_upserted')->default(0);
                $table->unsignedInteger('invoices_upserted')->default(0);
                $table->unsignedInteger('invoices_closed')->default(0);
                $table->text('error_message')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('finished_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('busy_sync_logs');

        Schema::table('parties', function (Blueprint $table) {
            $table->dropIndex(['busy_party_id']);
            if (Schema::hasColumn('parties', 'gstin')) {
                $table->dropColumn('gstin');
            }
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['busy_invoice_id']);
        });
    }
};
