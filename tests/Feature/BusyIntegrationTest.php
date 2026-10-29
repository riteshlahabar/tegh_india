<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Party;
use App\Models\User;
use App\Services\Busy\BusyApiClient;
use App\Services\Busy\BusyApiException;
use App\Services\Busy\BusyEndpoints;
use App\Services\Busy\BusySyncService;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BusyIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'busy.base_url' => 'http://api.apnabazarapp.in/WebAPI/V2',
            'busy.username' => 'tegh-user',
            'busy.password' => 'secret',
            'busy.timeout' => 10,
        ]);
    }

    public function test_catalog_covers_every_apna_bazar_endpoint(): void
    {
        $this->assertCount(32, BusyEndpoints::CATALOG);
        $this->assertArrayHasKey('get_outstanding', BusyEndpoints::CATALOG);
        $this->assertArrayHasKey('get_accounts', BusyEndpoints::CATALOG);
        $this->assertSame('add_acccounts.php', BusyEndpoints::CATALOG['add_accounts']['path']);
        $this->assertSame('get_listofvoucherswithdetails.php', BusyEndpoints::CATALOG['get_list_of_vouchers_with_details']['path']);
    }

    public function test_client_posts_credentials_to_every_catalogued_endpoint(): void
    {
        Http::fake(['*' => Http::response(['status' => 'success', 'data' => []], 200)]);

        $client = app(BusyApiClient::class);

        foreach (array_keys(BusyEndpoints::CATALOG) as $action) {
            $result = $client->call($action, [
                'Start_date' => '2026-04-01',
                'End_date' => '2026-08-29',
                'sd' => '2026-04-01',
                'ed' => '2026-08-29',
            ]);
            $this->assertIsArray($result);
        }

        Http::assertSentCount(count(BusyEndpoints::CATALOG));
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'get_outstanding.php')
                && $request['Username'] === 'tegh-user'
                && $request['Password'] === 'secret';
        });
        Http::assertSent(fn ($request) => str_contains($request->url(), 'get_accounts.php'));
        Http::assertSent(fn ($request) => str_contains($request->url(), 'get_listofvouchers.php'));
        Http::assertSent(fn ($request) => str_contains($request->url(), 'add_acccounts.php'));
    }

    public function test_client_rejects_unknown_actions_and_missing_credentials(): void
    {
        $this->expectException(BusyApiException::class);
        app(BusyApiClient::class)->call('not_a_real_action');
    }

    public function test_client_requires_credentials(): void
    {
        config(['busy.username' => '', 'busy.password' => '']);
        $this->expectException(BusyApiException::class);
        app(BusyApiClient::class)->getOutstanding();
    }

    public function test_sync_upserts_parties_and_invoices_from_busy(): void
    {
        Http::fake($this->syncHttpFake());

        $summary = app(BusySyncService::class)->sync('test');

        $this->assertGreaterThan(0, $summary['parties_upserted']);
        $this->assertGreaterThan(0, $summary['invoices_upserted']);

        $party = Party::where('busy_party_id', 'P-1001')->first();
        $this->assertNotNull($party);
        $this->assertSame('ABC Electricals Pvt Ltd', $party->name);
        $this->assertSame('9810097411', $party->mobile);

        $invoice = Invoice::where('busy_invoice_id', 'INV-UID-2500')->first();
        $this->assertNotNull($invoice);
        $this->assertSame($party->id, $invoice->party_id);
        $this->assertSame('INV-2500', $invoice->invoice_no);
        $this->assertEquals(245000.0, (float) $invoice->outstanding);
        $this->assertEquals(50000.0, (float) $invoice->received_amount);
    }

    public function test_sync_does_not_overwrite_crm_followup_fields(): void
    {
        $assignee = User::factory()->create(['role' => 'telecaller']);
        $party = Party::create([
            'name' => 'Old Name',
            'code' => 'TC-2000',
            'busy_party_id' => 'P-1001',
            'assigned_to' => $assignee->id,
        ]);
        Invoice::create([
            'party_id' => $party->id,
            'invoice_no' => 'INV-2500',
            'busy_invoice_id' => 'INV-UID-2500',
            'invoice_date' => '2026-07-01',
            'due_date' => '2026-07-31',
            'invoice_amount' => 295000,
            'received_amount' => 0,
            'outstanding' => 295000,
            'assigned_to' => $assignee->id,
            'next_followup_on' => '2026-09-01',
        ]);

        Http::fake($this->syncHttpFake());

        app(BusySyncService::class)->sync('test');

        $party->refresh();
        $invoice = Invoice::where('busy_invoice_id', 'INV-UID-2500')->first();

        $this->assertSame($assignee->id, $party->assigned_to);
        $this->assertSame($assignee->id, $invoice->assigned_to);
        $this->assertSame('2026-09-01', optional($invoice->next_followup_on)->toDateString());
        $this->assertEquals(245000.0, (float) $invoice->outstanding);
        $this->assertSame('ABC Electricals Pvt Ltd', $party->name);
    }

    public function test_sync_marks_invoices_paid_when_they_leave_outstanding(): void
    {
        $party = Party::create([
            'name' => 'Paid Party',
            'code' => 'TC-PAID',
            'busy_party_id' => 'P-1001',
        ]);
        Invoice::create([
            'party_id' => $party->id,
            'invoice_no' => 'INV-OLD',
            'busy_invoice_id' => 'INV-OLD-1',
            'invoice_date' => '2026-01-01',
            'due_date' => '2026-01-31',
            'invoice_amount' => 10000,
            'received_amount' => 0,
            'outstanding' => 10000,
        ]);

        Http::fake($this->syncHttpFake());

        app(BusySyncService::class)->sync('test');

        $closed = Invoice::where('busy_invoice_id', 'INV-OLD-1')->first();
        $this->assertEquals(0.0, (float) $closed->outstanding);
        $this->assertSame('Paid', $closed->status);
    }

    public function test_admin_can_list_and_call_busy_apis(): void
    {
        Http::fake(['*' => Http::response(['status' => 'success', 'data' => [['Name' => 'X']]], 200)]);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/admin/busy')
            ->assertOk()
            ->assertSee('Fetch from BUSY')
            ->assertSee('What do you want to see?')
            ->assertSee('Get Account Outstanding')
            ->assertSee('Add New Account Master')
            ->assertSee('Show records');

        $this->actingAs($admin)
            ->getJson('/admin/busy/api')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(32, 'data');

        $this->actingAs($admin)
            ->postJson('/admin/busy/api/get_outstanding')
            ->assertOk()
            ->assertJsonPath('success', true);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'get_outstanding.php'));
    }

    public function test_telecaller_cannot_call_busy_api_proxy(): void
    {
        $user = User::factory()->create(['role' => 'telecaller']);

        $this->actingAs($user)
            ->get('/admin/busy')
            ->assertForbidden();

        $this->actingAs($user)
            ->postJson('/admin/busy/api/get_outstanding')
            ->assertForbidden();
    }

    public function test_artisan_busy_sync_runs_the_sync_service(): void
    {
        Http::fake($this->syncHttpFake());

        $this->artisan('busy:sync')->assertSuccessful();

        $this->assertDatabaseHas('parties', ['busy_party_id' => 'P-1001']);
        $this->assertDatabaseHas('invoices', ['busy_invoice_id' => 'INV-UID-2500']);
    }

    public function test_sync_preserves_outstanding_when_sales_vouchers_present_and_stores_narration(): void
    {
        $ok = fn (array $data) => Http::response(['status' => 'success', 'data' => $data], 200);

        Http::fake([
            'api.apnabazarapp.in/WebAPI/V2/get_accounts.php' => $ok([
                [
                    'ERP_code' => 'P-1001',
                    'Name' => 'ABC Electricals Pvt Ltd',
                    'Alias' => 'TC-2000',
                ],
            ]),
            'api.apnabazarapp.in/WebAPI/V2/get_outstanding.php' => $ok([
                [
                    'ERP_code' => 'P-1001',
                    'PartyName' => 'ABC Electricals Pvt Ltd',
                    'Inv_No' => 'TCIPL/5000/26-27',
                    'Inv_Date' => '2026-10-01',
                    'DueDate' => '2026-10-31',
                    'Inv_Amount' => 150000,
                    'Outstanding_Amt' => 150000,
                    'Narration' => 'Project Alpha Payment Pending',
                ],
            ]),
            'api.apnabazarapp.in/WebAPI/V2/get_listofvouchers.php' => $ok([
                [
                    'Date' => '2026-10-01 00:00:00',
                    'VchCode' => 9999,
                    'VoucherNumber' => 'TCIPL/5000/26-27',
                    'VchType' => 9,
                    'Account' => 'ABC Electricals Pvt Ltd',
                    'Amount' => 150000,
                ],
                [
                    'Date' => '2026-10-02 00:00:00',
                    'VchCode' => 8888,
                    'VoucherNumber' => 'TCIPL/9999/26-27',
                    'VchType' => 9,
                    'Account' => 'ABC Electricals Pvt Ltd',
                    'Amount' => 0,
                ],
            ]),
            'api.apnabazarapp.in/WebAPI/V2/get_CancelledVoucherList.php' => $ok([]),
            '*' => $ok([]),
        ]);

        app(BusySyncService::class)->sync('test');

        $invoice = Invoice::where('invoice_no', 'TCIPL/5000/26-27')->first();
        $this->assertNotNull($invoice);
        $this->assertEquals(150000.0, (float) $invoice->outstanding);
        $this->assertEquals(0.0, (float) $invoice->received_amount);
        $this->assertSame('Project Alpha Payment Pending', $invoice->narration);
        $this->assertSame('Project Alpha Payment Pending', $invoice->po_no);
        $this->assertNotSame('Paid', $invoice->computed_status);

        // Ensure TCIPL/9999/26-27 was NOT created as a zombie $0 invoice
        $this->assertNull(Invoice::where('invoice_no', 'TCIPL/9999/26-27')->first());
    }

    /**
     * @return array<string, PromiseInterface>
     */
    private function syncHttpFake(): array
    {
        $ok = fn (array $data) => Http::response(['status' => 'success', 'data' => $data], 200);

        return [
            'api.apnabazarapp.in/WebAPI/V2/get_accounts.php' => $ok([
                [
                    'ERP_code' => 'P-1001',
                    'Name' => 'ABC Electricals Pvt Ltd',
                    'Alias' => 'TC-2000',
                    'Mobile' => '9810097411',
                    'Email' => 'accounts@abc.com',
                    'City' => 'Mumbai',
                    'State' => 'Maharashtra',
                    'Gst_no' => '27AABCU9603R1ZM',
                    'Print_name' => 'ABC Electricals',
                ],
            ]),
            'api.apnabazarapp.in/WebAPI/V2/get_outstanding.php' => $ok([
                [
                    'ERP_code' => 'P-1001',
                    'PartyName' => 'ABC Electricals Pvt Ltd',
                    'Alias' => 'TC-2000',
                    'BillNo' => 'INV-2500',
                    'VchCode' => 'INV-UID-2500',
                    'BillDate' => '2026-07-01',
                    'DueDate' => '2026-07-31',
                    'BillAmt' => 295000,
                    'RecdAmt' => 50000,
                    'BalAmt' => 245000,
                    'SalesPerson' => 'R. Bhosale',
                    'Branch' => 'Mumbai HQ',
                ],
            ]),
            'api.apnabazarapp.in/WebAPI/V2/get_listofvouchers.php' => $ok([]),
            'api.apnabazarapp.in/WebAPI/V2/get_CancelledVoucherList.php' => $ok([]),
            '*' => $ok([]),
        ];
    }
}
