<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Party;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TelecallerApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_add_a_telecaller_and_that_login_opens_the_app(): void
    {
        $admin = User::factory()->admin()->create([
            'username' => 'admin',
            'email' => 'admin@teghcables.com',
            'password' => '1234',
        ]);

        $this->actingAs($admin)->post('/admin/telecallers', [
            'name' => 'Neha Sharma',
            'username' => 'neha',
            'email' => 'neha@teghcables.com',
            'password' => 'pass1234',
            'branch' => 'Mumbai HQ',
        ])->assertRedirect('/admin/users');

        $this->assertDatabaseHas('users', [
            'username' => 'neha',
            'role' => 'telecaller',
        ]);

        $login = $this->postJson('/api/login', [
            'username' => 'neha',
            'password' => 'pass1234',
        ])->assertOk()->assertJsonPath('user.username', 'neha');

        $token = $login->json('token');

        $this->withToken($token)->getJson('/api/dashboard')->assertOk()->assertJsonStructure([
            'kpis' => ['total_outstanding_inr', 'open_invoices'],
            'followups',
            'team',
        ]);

        $this->withToken($token)->getJson('/api/outstanding')->assertOk();
        $this->withToken($token)->getJson('/api/followups')->assertOk();
        $this->withToken($token)->getJson('/api/customers')->assertOk();
        $this->withToken($token)->getJson('/api/ptp')->assertOk();
        $this->withToken($token)->getJson('/api/calls')->assertOk();
        $this->withToken($token)->getJson('/api/whatsapp')->assertOk();
        $this->withToken($token)->getJson('/api/email')->assertOk();
        $this->withToken($token)->getJson('/api/reports')->assertOk();
        $this->withToken($token)->getJson('/api/team')->assertOk();
        $this->withToken($token)->getJson('/api/meta')->assertOk()->assertJsonFragment(['Connected']);
    }

    public function test_telecaller_can_save_a_follow_up_from_the_app(): void
    {
        $user = User::factory()->create([
            'username' => 'aakanksha',
            'role' => 'telecaller',
            'password' => '1234',
        ]);

        $party = Party::create([
            'name' => 'Acme Electricals',
            'code' => 'ACME',
            'mobile' => '9876543210',
            'email' => 'accounts@acme.test',
            'busy_party_id' => 'PARTY-ACME',
        ]);

        $invoice = Invoice::create([
            'party_id' => $party->id,
            'invoice_no' => 'TCIPL-1001',
            'invoice_date' => now()->subDays(20)->toDateString(),
            'due_date' => now()->subDays(5)->toDateString(),
            'invoice_amount' => 25000,
            'received_amount' => 0,
            'outstanding' => 25000,
            'busy_invoice_id' => 'INV-1001',
            'assigned_to' => $user->id,
        ]);

        $token = $this->postJson('/api/login', [
            'username' => 'aakanksha',
            'password' => '1234',
        ])->json('token');

        $this->withToken($token)->postJson('/api/calls', [
            'invoice_id' => $invoice->id,
            'outcome' => 'Payment Promise',
            'remarks' => 'Will pay Friday',
            'promise_amount' => 25000,
            'promise_date' => now()->addDays(2)->toDateString(),
            'next_followup_on' => now()->addDays(2)->toDateString(),
        ])->assertOk()->assertJsonPath('ok', true);

        $this->assertDatabaseHas('calls', [
            'invoice_id' => $invoice->id,
            'telecaller_id' => $user->id,
            'outcome' => 'Payment Promise',
        ]);
    }

    public function test_telecaller_cannot_create_another_telecaller(): void
    {
        $user = User::factory()->create(['role' => 'telecaller']);

        $this->actingAs($user)->post('/admin/telecallers', [
            'name' => 'Other',
            'username' => 'other',
            'email' => 'other@teghcables.com',
            'password' => '1234',
        ])->assertForbidden();
    }
}
