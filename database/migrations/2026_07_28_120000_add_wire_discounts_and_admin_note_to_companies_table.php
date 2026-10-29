<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->boolean('house_wire_discount')->default(false)->after('enquiry_product_name');
            $table->boolean('flexible_wire_discount')->default(false)->after('house_wire_discount');
            $table->boolean('other_wire_discount')->default(false)->after('flexible_wire_discount');
            $table->text('enquiry_admin_note')->nullable()->after('other_wire_discount');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'house_wire_discount',
                'flexible_wire_discount',
                'other_wire_discount',
                'enquiry_admin_note',
            ]);
        });
    }
};
