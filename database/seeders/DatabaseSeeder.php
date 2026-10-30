<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Tegh Admin',
            'username' => 'admin',
            'email' => 'admin@teghcables.com',
            'password' => Hash::make('1234'),
            'role' => 'admin',
            'initials' => 'TA',
            'branch' => 'All',
        ]);

        User::create([
            'name' => 'Aakanksha',
            'username' => 'aakanksha',
            'email' => 'aakanksha@teghcables.com',
            'password' => Hash::make('1234'),
            'role' => 'telecaller',
            'initials' => 'AK',
            'branch' => 'Mumbai HQ',
            'region' => 'mumbai',
        ]);
        User::create([
            'name' => 'Javed',
            'username' => 'javed',
            'email' => 'javed@teghcables.com',
            'password' => Hash::make('1234'),
            'role' => 'telecaller',
            'initials' => 'JV',
            'branch' => 'Bhiwandi Plant',
            'region' => 'mumbai',
        ]);
        User::create([
            'name' => 'Amol',
            'username' => 'amol',
            'email' => 'amol@teghcables.com',
            'password' => Hash::make('1234'),
            'role' => 'telecaller',
            'initials' => 'AM',
            'branch' => 'Pune Branch',
            'region' => 'pune',
        ]);

        AppSetting::setValue('company_name', 'Tegh Cables India Pvt. Ltd.');
        AppSetting::setValue('gstin', '27AACCT1234F1Z5');
        AppSetting::setValue('registered_office', 'MIDC Industrial Area, Bhiwandi, Maharashtra');
        AppSetting::setValue('followup_reminder', '1 day before due date');
        AppSetting::setValue('busy_status', 'Not connected');
        AppSetting::setValue('busy_last_sync', 'Never');
    }
}
