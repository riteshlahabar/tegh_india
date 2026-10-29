<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('parties') && ! Schema::hasColumn('parties', 'gstin')) {
            Schema::table('parties', function (Blueprint $table) {
                $table->string('gstin')->nullable()->after('email');
            });
        }

        if (Schema::hasTable('parties') && ! $this->hasIndex('parties', 'busy_party_id')) {
            Schema::table('parties', function (Blueprint $table) {
                $table->index('busy_party_id');
            });
        }

        if (Schema::hasTable('invoices') && ! $this->hasIndex('invoices', 'busy_invoice_id')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->index('busy_invoice_id');
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
                $table->string('status')->default('upcoming');
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
        Schema::dropIfExists('email_messages');
        Schema::dropIfExists('whatsapp_messages');
        Schema::dropIfExists('payment_promises');
    }

    private function hasIndex(string $table, string $column): bool
    {
        return collect(Schema::getIndexes($table))->contains(function (array $index) use ($column) {
            return ($index['columns'] ?? []) === [$column];
        });
    }
};
