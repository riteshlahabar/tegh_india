<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            if (! Schema::hasColumn('quotations', 'whatsapp_sent_at')) {
                $table->timestamp('whatsapp_sent_at')->nullable()->after('emailed_to');
            }
            if (! Schema::hasColumn('quotations', 'whatsapp_sent_to')) {
                $table->string('whatsapp_sent_to')->nullable()->after('whatsapp_sent_at');
            }
            if (! Schema::hasColumn('quotations', 'whatsapp_message')) {
                $table->text('whatsapp_message')->nullable()->after('whatsapp_sent_to');
            }
        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            foreach (['whatsapp_message', 'whatsapp_sent_to', 'whatsapp_sent_at'] as $col) {
                if (Schema::hasColumn('quotations', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
