<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('apna_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('erp_code')->unique();
            $table->string('name');
            $table->string('alias')->nullable();
            $table->string('print_name')->nullable();
            $table->string('parent_group')->nullable();
            $table->string('mobile')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('email')->nullable();
            $table->string('gst_number')->nullable();
            $table->string('contact')->nullable();
            $table->string('state')->nullable();
            $table->string('pin_code')->nullable();
            $table->text('address')->nullable();
            $table->decimal('credit_limit', 15, 2)->nullable();
            $table->unsignedInteger('credit_days')->nullable();
            $table->decimal('closing_balance', 15, 2)->nullable();
            $table->unsignedInteger('master_status')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });

        Schema::create('apna_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('erp_code')->unique();
            $table->string('name');
            $table->string('alias')->nullable();
            $table->string('print_name')->nullable();
            $table->string('parent_group')->nullable();
            $table->string('unit')->nullable();
            $table->string('hsn_code')->nullable();
            $table->decimal('sale_price', 15, 2)->nullable();
            $table->decimal('mrp', 15, 2)->nullable();
            $table->decimal('closing_stock', 15, 3)->nullable();
            $table->decimal('tax_percentage', 8, 2)->nullable();
            $table->unsignedInteger('master_status')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });

        Schema::create('apna_groups', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 20);
            $table->unsignedBigInteger('erp_code');
            $table->string('name');
            $table->string('parent_name')->nullable();
            $table->unsignedBigInteger('busy_mast_code')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
            $table->unique(['kind', 'erp_code']);
        });

        Schema::create('apna_outstandings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('erp_code')->nullable();
            $table->string('account_name')->nullable();
            $table->string('invoice_no')->nullable();
            $table->date('invoice_date')->nullable();
            $table->date('due_date')->nullable();
            $table->decimal('invoice_amount', 15, 2)->nullable();
            $table->decimal('outstanding_amount', 15, 2)->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });

        Schema::create('apna_vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 20);
            $table->string('voucher_id');
            $table->string('voucher_no')->nullable();
            $table->string('voucher_type')->nullable();
            $table->string('status')->nullable();
            $table->date('tran_date')->nullable();
            $table->decimal('amount', 15, 2)->nullable();
            $table->text('narration')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
            $table->unique(['kind', 'voucher_id']);
        });

        Schema::create('apna_pending_orders', function (Blueprint $table) {
            $table->id();
            $table->string('item')->nullable();
            $table->string('account_name')->nullable();
            $table->string('order_ref')->nullable();
            $table->date('order_date')->nullable();
            $table->date('due_date')->nullable();
            $table->decimal('order_qty', 15, 3)->nullable();
            $table->decimal('pending_qty', 15, 3)->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });

        Schema::create('apna_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('endpoint');
            $table->string('status', 20);
            $table->unsignedInteger('record_count')->default(0);
            $table->text('message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apna_sync_logs');
        Schema::dropIfExists('apna_pending_orders');
        Schema::dropIfExists('apna_vouchers');
        Schema::dropIfExists('apna_outstandings');
        Schema::dropIfExists('apna_groups');
        Schema::dropIfExists('apna_items');
        Schema::dropIfExists('apna_accounts');
    }
};
