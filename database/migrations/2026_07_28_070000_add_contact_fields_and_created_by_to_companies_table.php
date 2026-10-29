<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('contact_person_name')->nullable()->after('enquiry_product_name');
            $table->string('mobile_number', 20)->nullable()->after('contact_person_name');
            $table->string('whatsapp_number', 20)->nullable()->after('mobile_number');
            $table->string('email')->nullable()->after('whatsapp_number');
            $table->foreignId('created_by')->nullable()->after('assigned_tc_id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn([
                'contact_person_name',
                'mobile_number',
                'whatsapp_number',
                'email',
            ]);
        });
    }
};
