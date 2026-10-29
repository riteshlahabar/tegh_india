<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('industry')->nullable();
            $table->string('city')->nullable();
            $table->text('address')->nullable();
            $table->string('gst_number')->nullable();
            $table->decimal('credit_limit', 15, 2)->default(0);
            $table->string('payment_terms')->nullable();
            $table->decimal('outstanding_amount', 15, 2)->default(0);
            $table->string('stage')->default('New'); // New, Follow-up, Quotation Sent, Negotiation, Won, Lost
            $table->foreignId('assigned_exec_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('assigned_tc_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('executive_id')->constrained('users')->onDelete('cascade');
            $table->dateTime('checked_in_at');
            $table->dateTime('checked_out_at')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('proof_photo_path')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('checked_in'); // checked_in, checked_out
            $table->timestamps();
        });

        Schema::create('calls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('telecaller_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->dateTime('called_at');
            $table->string('outcome'); // Interested, Callback, Quotation requested, Not reachable
            $table->text('remarks')->nullable();
            $table->dateTime('next_followup')->nullable();
            $table->timestamps();
        });

        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('quotation_number');
            $table->decimal('amount', 15, 2);
            $table->string('status')->default('Sent'); // Sent, Approved, Lost
            $table->dateTime('date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotations');
        Schema::dropIfExists('calls');
        Schema::dropIfExists('visits');
        Schema::dropIfExists('companies');
        Schema::dropIfExists('cities');
    }
};
