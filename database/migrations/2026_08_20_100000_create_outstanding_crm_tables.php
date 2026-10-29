<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('parties')) {
            Schema::create('parties', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code')->unique();
                $table->string('contact_person')->nullable();
                $table->string('mobile', 20)->nullable();
                $table->string('email')->nullable();
                $table->string('city')->nullable();
                $table->string('state')->nullable();
                $table->string('sales_person')->nullable();
                $table->string('branch')->nullable();
                $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
                $table->decimal('credit_limit', 15, 2)->default(0);
                $table->string('payment_terms')->nullable();
                $table->string('busy_party_id')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('invoices')) {
            Schema::create('invoices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('party_id')->constrained('parties')->cascadeOnDelete();
                $table->string('invoice_no')->unique();
                $table->string('po_no')->nullable();
                $table->date('invoice_date');
                $table->date('due_date');
                $table->decimal('invoice_amount', 15, 2)->default(0);
                $table->decimal('received_amount', 15, 2)->default(0);
                $table->decimal('outstanding', 15, 2)->default(0);
                $table->string('status')->default('Not Due');
                $table->string('priority')->default('low'); // high, med, low
                $table->string('sales_person')->nullable();
                $table->string('branch')->nullable();
                $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('last_followup_at')->nullable();
                $table->date('next_followup_on')->nullable();
                $table->string('busy_invoice_id')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('calls')) {
            Schema::create('calls', function (Blueprint $table) {
                $table->id();
                $table->foreignId('party_id')->constrained('parties')->cascadeOnDelete();
                $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
                $table->foreignId('telecaller_id')->nullable()->constrained('users')->nullOnDelete();
                $table->dateTime('called_at');
                $table->string('outcome');
                $table->text('remarks')->nullable();
                $table->date('next_followup_on')->nullable();
                $table->decimal('promise_amount', 15, 2)->nullable();
                $table->date('promise_date')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('payment_promises')) {
            Schema::create('payment_promises', function (Blueprint $table) {
                $table->id();
                $table->foreignId('party_id')->constrained('parties')->cascadeOnDelete();
                $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
                $table->foreignId('telecaller_id')->nullable()->constrained('users')->nullOnDelete();
                $table->decimal('amount', 15, 2);
                $table->date('promise_date');
                $table->timestamp('collected_at')->nullable();
                $table->string('status')->default('upcoming'); // upcoming, due_today, broken, collected
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('whatsapp_messages')) {
            Schema::create('whatsapp_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('party_id')->constrained('parties')->cascadeOnDelete();
                $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('mobile')->nullable();
                $table->string('template')->nullable();
                $table->text('message');
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('email_messages')) {
            Schema::create('email_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('party_id')->constrained('parties')->cascadeOnDelete();
                $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('to_email');
                $table->string('cc')->nullable();
                $table->string('subject');
                $table->text('body');
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('app_settings')) {
            Schema::create('app_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
        Schema::dropIfExists('email_messages');
        Schema::dropIfExists('whatsapp_messages');
        Schema::dropIfExists('payment_promises');
        Schema::dropIfExists('calls');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('parties');
    }
};
