<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('house_wire_discount_note')->nullable()->after('house_wire_discount');
            $table->string('flexible_wire_discount_note')->nullable()->after('flexible_wire_discount');
            $table->string('other_wire_discount_note')->nullable()->after('other_wire_discount');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'house_wire_discount_note',
                'flexible_wire_discount_note',
                'other_wire_discount_note',
            ]);
        });
    }
};
