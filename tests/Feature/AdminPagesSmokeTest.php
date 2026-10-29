<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Party;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPagesSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_all_admin_pages_render(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();

        $routes = [
            '/',
            '/admin/outstanding',
            '/admin/followups',
            '/admin/customers',
            '/admin/ptp',
            '/admin/calls',
            '/admin/whatsapp',
            '/admin/email',
            '/admin/reports',
            '/admin/users',
            '/admin/busy',
            '/admin/settings',
        ];

        foreach ($routes as $uri) {
            $this->actingAs($admin)->get($uri)->assertOk();
        }
    }

    public function test_dummy_invoices_are_not_listed(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();
        $demoParty = Party::withoutGlobalScopes()->create([
            'name' => 'ABC Electricals Pvt Ltd',
            'code' => 'TC-2000',
            'busy_party_id' => 'BUSY-1000',
        ]);
        Invoice::withoutGlobalScopes()->create([
            'party_id' => $demoParty->id,
            'invoice_no' => 'INV-2500',
            'busy_invoice_id' => 'BINV-2500',
            'invoice_date' => now()->subMonth(),
            'due_date' => now()->subDays(10),
            'invoice_amount' => 250000,
            'received_amount' => 0,
            'outstanding' => 250000,
        ]);
        $liveParty = Party::create([
            'name' => 'ADARSH TREDING CO',
            'code' => 'CUS00706',
            'busy_party_id' => '149',
        ]);
        Invoice::create([
            'party_id' => $liveParty->id,
            'invoice_no' => 'INV/30',
            'busy_invoice_id' => '888',
            'invoice_date' => now()->subMonth(),
            'due_date' => now()->subDays(10),
            'invoice_amount' => 779675,
            'received_amount' => 0,
            'outstanding' => 779675,
        ]);

        $this->actingAs($admin)->get('/admin/reports?type=outstanding')
            ->assertOk()
            ->assertDontSee('ABC Electricals Pvt Ltd')
            ->assertSee('ADARSH TREDING CO');
    }

    public function test_admin_outstanding_shows_only_tcipl_invoices_and_dashboard_cards_link(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();
        $party = Party::create([
            'name' => 'Tegh Buyer',
            'code' => 'TC-9001',
            'busy_party_id' => '9001',
        ]);
        Invoice::create([
            'party_id' => $party->id,
            'invoice_no' => 'JV-100',
            'busy_invoice_id' => 'JV-100',
            'invoice_date' => now()->subMonth(),
            'due_date' => now()->subDays(5),
            'invoice_amount' => 1000,
            'received_amount' => 0,
            'outstanding' => 1000,
        ]);
        Invoice::create([
            'party_id' => $party->id,
            'invoice_no' => 'TCIPL/100',
            'busy_invoice_id' => 'TCIPL-100',
            'invoice_date' => now()->subMonth(),
            'due_date' => now()->subDays(5),
            'invoice_amount' => 2000,
            'received_amount' => 0,
            'outstanding' => 2000,
        ]);

        $this->actingAs($admin)->get('/admin/outstanding')
            ->assertOk()
            ->assertSee('TCIPL/100')
            ->assertDontSee('JV-100');

        $this->actingAs($admin)->get('/')
            ->assertOk()
            ->assertSee(route('admin.outstanding'), false)
            ->assertSee(route('admin.outstanding', ['status' => 'Overdue']), false)
            ->assertSee(route('admin.ptp'), false)
            ->assertSee(route('admin.followups'), false);
    }

    public function test_telecaller_can_log_a_call(): void
    {
        $user = User::where('username', 'aakanksha')->firstOrFail();
        $party = Party::create([
            'name' => 'Busy Sync Party',
            'code' => 'ERP-291',
            'busy_party_id' => '291',
        ]);
        $invoice = Invoice::create([
            'party_id' => $party->id,
            'invoice_no' => 'INV/30',
            'busy_invoice_id' => 'VCH-30',
            'invoice_date' => now()->subMonth(),
            'due_date' => now()->subDay(),
            'invoice_amount' => 10000,
            'received_amount' => 0,
            'outstanding' => 10000,
        ]);

        $this->actingAs($user)->post('/admin/calls', [
            'invoice_id' => $invoice->id,
            'outcome' => 'Connected',
            'remarks' => 'Followed up with accounts.',
            'next_followup_on' => now()->addDay()->toDateString(),
        ])->assertRedirect();

        $this->assertDatabaseHas('calls', [
            'invoice_id' => $invoice->id,
            'outcome' => 'Connected',
            'telecaller_id' => $user->id,
        ]);
    }

    public function test_outstanding_page_filters_by_city(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();
        $puneParty = Party::create([
            'name' => 'Pune Traders',
            'code' => 'PUN-01',
            'city' => 'Pune',
            'busy_party_id' => '1001',
        ]);
        $delhiParty = Party::create([
            'name' => 'Delhi Traders',
            'code' => 'DEL-01',
            'city' => 'Delhi',
            'busy_party_id' => '1002',
        ]);

        Invoice::create([
            'party_id' => $puneParty->id,
            'invoice_no' => 'TCIPL/PUN/01',
            'busy_invoice_id' => 'VCH-PUN-01',
            'invoice_date' => now()->subMonth(),
            'due_date' => now()->subDay(),
            'invoice_amount' => 50000,
            'received_amount' => 0,
            'outstanding' => 50000,
        ]);
        Invoice::create([
            'party_id' => $delhiParty->id,
            'invoice_no' => 'TCIPL/DEL/01',
            'busy_invoice_id' => 'VCH-DEL-01',
            'invoice_date' => now()->subMonth(),
            'due_date' => now()->subDay(),
            'invoice_amount' => 60000,
            'received_amount' => 0,
            'outstanding' => 60000,
        ]);

        $res = $this->actingAs($admin)->get('/admin/outstanding?city=Pune');
        $res->assertOk();
        $invoices = $res->viewData('invoices');
        $this->assertEquals(1, $invoices->total());
        $this->assertEquals('TCIPL/PUN/01', $invoices->first()->invoice_no);
    }

    public function test_outstanding_export_includes_city(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();
        $puneParty = Party::create([
            'name' => 'Export Pune Party',
            'code' => 'EXP-01',
            'city' => 'Pune',
            'busy_party_id' => '1003',
        ]);
        Invoice::create([
            'party_id' => $puneParty->id,
            'invoice_no' => 'TCIPL/EXP/01',
            'busy_invoice_id' => 'VCH-EXP-01',
            'invoice_date' => now()->subMonth(),
            'due_date' => now()->subDay(),
            'invoice_amount' => 50000,
            'received_amount' => 0,
            'outstanding' => 50000,
        ]);

        $res = $this->actingAs($admin)->get('/admin/outstanding/export?city=Pune');
        $res->assertOk();
        $content = $res->streamedContent();
        $this->assertStringContainsString('Pune', $content);
        $this->assertStringContainsString('TCIPL/EXP/01', $content);
    }
}
