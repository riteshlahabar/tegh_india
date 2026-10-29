<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('product_code')->nullable()->after('name');
            $table->string('usage_unit')->nullable()->after('product_code');
            $table->text('description')->nullable()->after('usage_unit');
            $table->string('category')->nullable()->after('description');
            $table->string('hsn_code', 50)->nullable()->after('category');
            $table->string('mts', 50)->nullable()->after('hsn_code');
            $table->string('colour', 100)->nullable()->after('mts');
            $table->string('size_sqmm', 50)->nullable()->after('colour');
            $table->string('core', 50)->nullable()->after('size_sqmm');
            $table->string('spec', 100)->nullable()->after('core');
            $table->string('unit', 50)->nullable()->after('spec');
            $table->string('des1')->nullable()->after('unit');
            $table->string('des2')->nullable()->after('des1');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'product_code',
                'usage_unit',
                'description',
                'category',
                'hsn_code',
                'mts',
                'colour',
                'size_sqmm',
                'core',
                'spec',
                'unit',
                'des1',
                'des2',
            ]);
        });
    }
};
