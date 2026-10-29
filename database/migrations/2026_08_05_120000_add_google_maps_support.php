<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        DB::table('app_settings')->insert([
            [
                'key' => 'google_maps_enabled',
                'value' => '0',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        Schema::table('companies', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('address');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('branch');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->string('current_address')->nullable()->after('longitude');
            $table->timestamp('last_location_at')->nullable()->after('current_address');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude', 'current_address', 'last_location_at']);
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });

        Schema::dropIfExists('app_settings');
    }
};
