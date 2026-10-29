<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            if (! Schema::hasColumn('quotations', 'sales_person_name')) {
                $table->string('sales_person_name')->nullable()->after('notes');
            }
            if (! Schema::hasColumn('quotations', 'sales_person_mobile')) {
                $table->string('sales_person_mobile')->nullable()->after('sales_person_name');
            }
            if (! Schema::hasColumn('quotations', 'payment_terms')) {
                $table->string('payment_terms')->nullable()->after('sales_person_mobile');
            }
            if (! Schema::hasColumn('quotations', 'terms_conditions')) {
                $table->text('terms_conditions')->nullable()->after('payment_terms');
            }
            if (! Schema::hasColumn('quotations', 'product_make')) {
                $table->string('product_make')->nullable()->after('terms_conditions');
            }
            if (! Schema::hasColumn('quotations', 'party_gstin')) {
                $table->string('party_gstin')->nullable()->after('product_make');
            }
            if (! Schema::hasColumn('quotations', 'party_mobile')) {
                $table->string('party_mobile')->nullable()->after('party_gstin');
            }
            if (! Schema::hasColumn('quotations', 'party_address')) {
                $table->text('party_address')->nullable()->after('party_mobile');
            }
            if (! Schema::hasColumn('quotations', 'total_qty')) {
                $table->decimal('total_qty', 15, 2)->nullable()->after('party_address');
            }
        });

        if (! Schema::hasTable('quotation_items')) {
            Schema::create('quotation_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('quotation_id')->constrained('quotations')->cascadeOnDelete();
                $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
                $table->text('description');
                $table->string('hsn_code')->nullable();
                $table->decimal('qty', 15, 3)->default(0);
                $table->string('unit')->nullable();
                $table->decimal('list_price', 15, 2)->default(0);
                $table->decimal('discount_percent', 8, 2)->default(0);
                $table->decimal('price', 15, 4)->default(0);
                $table->decimal('amount', 15, 2)->default(0);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_items');

        Schema::table('quotations', function (Blueprint $table) {
            foreach ([
                'sales_person_name',
                'sales_person_mobile',
                'payment_terms',
                'terms_conditions',
                'product_make',
                'party_gstin',
                'party_mobile',
                'party_address',
                'total_qty',
            ] as $col) {
                if (Schema::hasColumn('quotations', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
