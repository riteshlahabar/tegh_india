<?php

namespace Tests\Feature;

use App\Models\Call;
use App\Models\Company;
use App\Models\Quotation;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReportsTelecallerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'branch' => 'All',
        ]);
    }

    private function telecaller(string $name, string $branch = 'Mumbai'): User
    {
        return User::factory()->create([
            'name' => $name,
            'role' => 'telecaller',
            'branch' => $branch,
        ]);
    }

    private function company(array $overrides = []): Company
    {
        return Company::create(array_merge([
            'name' => 'Report Co',
            'city' => 'Mumbai',
            'credit_limit' => 0,
            'outstanding_amount' => 0,
            'stage' => 'Follow-up',
        ], $overrides));
    }

    public function test_reports_page_shows_telecaller_detailed_report(): void
    {
        $admin = $this->admin();
        $pooja = $this->telecaller('Pooja Shinde');
        $neha = $this->telecaller('Neha Telecaller', 'Pune');

        $lead = $this->company([
            'name' => 'Balaji Wires',
            'assigned_tc_id' => $pooja->id,
            'stage' => 'Won',
        ]);
        $this->company([
            'name' => 'Pune Cables',
            'assigned_tc_id' => $neha->id,
            'stage' => 'New',
        ]);

        Call::create([
            'company_id' => $lead->id,
            'telecaller_id' => $pooja->id,
            'called_at' => now()->subHour(),
            'outcome' => 'Interested',
            'remarks' => 'Wants 2.5 sqmm pricing',
            'next_followup' => now()->addDay(),
        ]);

        Quotation::create([
            'company_id' => $lead->id,
            'quotation_number' => 'Q-9001/26-27',
            'amount' => 150000,
            'status' => 'Approved',
            'date' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.reports', ['tab' => 'telecaller']))
            ->assertOk()
            ->assertSee('data-report-tab="telecaller"', false)
            ->assertSee('Telecaller-wise Report')
            ->assertSee('Call Activity Detail')
            ->assertSee('Pooja Shinde')
            ->assertSee('Neha Telecaller')
            ->assertSee('Balaji Wires')
            ->assertSee('Wants 2.5 sqmm pricing')
            ->assertSee('Interested')
            ->assertSee('₹1,50,000');
    }

    public function test_telecaller_report_can_be_filtered_by_telecaller_and_date(): void
    {
        $admin = $this->admin();
        $pooja = $this->telecaller('Pooja Shinde');
        $neha = $this->telecaller('Neha Telecaller');

        $poojaLead = $this->company([
            'name' => 'Pooja Assigned Ltd',
            'assigned_tc_id' => $pooja->id,
        ]);
        $nehaLead = $this->company([
            'name' => 'Neha Assigned Ltd',
            'assigned_tc_id' => $neha->id,
        ]);

        Call::create([
            'company_id' => $poojaLead->id,
            'telecaller_id' => $pooja->id,
            'called_at' => '2026-08-10 10:00:00',
            'outcome' => 'Follow-Up',
            'remarks' => 'August follow-up from Pooja',
        ]);
        Call::create([
            'company_id' => $nehaLead->id,
            'telecaller_id' => $neha->id,
            'called_at' => '2026-08-20 10:00:00',
            'outcome' => 'Profile Shared',
            'remarks' => 'August profile from Neha',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.reports', [
                'tab' => 'telecaller',
                'telecaller_id' => $pooja->id,
            ]))
            ->assertOk()
            ->assertSee('Pooja Shinde')
            ->assertSee('August follow-up from Pooja')
            ->assertDontSee('August profile from Neha')
            ->assertSee('1 telecallers');

        $this->actingAs($admin)
            ->get(route('admin.reports', [
                'tab' => 'telecaller',
                'from' => '2026-08-15',
                'to' => '2026-08-31',
            ]))
            ->assertOk()
            ->assertSee('August profile from Neha')
            ->assertDontSee('August follow-up from Pooja');
    }

    public function test_customer_report_is_paginated_and_branding_uses_tegh_cables(): void
    {
        $admin = $this->admin();
        foreach (range(1, 3) as $i) {
            $this->company([
                'name' => sprintf('Paged Customer %02d Ltd', $i),
                'stage' => 'New',
            ]);
        }

        $page1 = $this->actingAs($admin)->get(route('admin.reports', [
            'tab' => 'customer',
            'per_page' => 2,
        ]));
        $page1->assertOk();
        $page1->assertSee('Tegh Cables Analytics');
        $page1->assertDontSee('TPI Analytics');
        $page1->assertSee('Showing 1–2 of 3', false);
        $page1->assertSee('tab=customer', false);

        $page2 = $this->actingAs($admin)->get(route('admin.reports', [
            'tab' => 'customer',
            'per_page' => 2,
            'page' => 2,
        ]));
        $page2->assertOk();
        $page2->assertSee('Showing 3–3 of 3', false);
        $page2->assertSee('Paged Customer 03 Ltd');
    }

    public function test_customer_report_activity_chips_open_on_the_same_page(): void
    {
        $admin = $this->admin();
        $company = $this->company(['name' => 'Activity Link Co']);

        $response = $this->actingAs($admin)->get(route('admin.reports', ['tab' => 'customer']));

        $response->assertOk();
        $response->assertSee('js-report-activity', false);
        $response->assertSee('data-type="visits"', false);
        $response->assertSee('data-type="quotes"', false);
        $response->assertSee('data-type="calls"', false);
        $response->assertSee('activity=visits', false);
        $response->assertSee('activity=quotes', false);
        $response->assertSee('activity=calls', false);
        $response->assertSee('0 visits', false);
        $response->assertSee('0 quotes', false);
        $response->assertSee('0 calls', false);
        $response->assertSee('modal-report-activity', false);
    }

    public function test_customer_report_can_filter_by_visits_quotes_and_calls(): void
    {
        $admin = $this->admin();
        $sales = User::factory()->create(['role' => 'sales_executive', 'branch' => 'Mumbai']);
        $visited = $this->company(['name' => 'Visited Party']);
        $quoted = $this->company(['name' => 'Quoted Party']);
        $called = $this->company(['name' => 'Called Party']);
        $this->company(['name' => 'Silent Party']);

        Visit::create([
            'company_id' => $visited->id,
            'executive_id' => $sales->id,
            'checked_in_at' => now()->subHour(),
            'status' => 'checked_out',
        ]);
        Quotation::create([
            'company_id' => $quoted->id,
            'quotation_number' => 'Q-ACT-1/26-27',
            'amount' => 12000,
            'status' => 'Sent',
            'date' => now(),
        ]);
        Call::create([
            'company_id' => $called->id,
            'telecaller_id' => $this->telecaller('Filter TC')->id,
            'called_at' => now()->subMinutes(30),
            'outcome' => 'Interested',
        ]);

        $this->actingAs($admin)->get(route('admin.reports', ['tab' => 'customer', 'activity' => 'visits']))
            ->assertOk()
            ->assertSee('data-company-name="Visited Party"', false)
            ->assertDontSee('data-company-name="Quoted Party"', false)
            ->assertDontSee('data-company-name="Called Party"', false)
            ->assertDontSee('data-company-name="Silent Party"', false)
            ->assertSee('id="report-customer-visible-count">1<', false);

        $this->actingAs($admin)->get(route('admin.reports', ['tab' => 'customer', 'activity' => 'quotes']))
            ->assertOk()
            ->assertSee('data-company-name="Quoted Party"', false)
            ->assertDontSee('data-company-name="Visited Party"', false)
            ->assertDontSee('data-company-name="Called Party"', false)
            ->assertDontSee('data-company-name="Silent Party"', false)
            ->assertSee('id="report-customer-visible-count">1<', false);

        $this->actingAs($admin)->get(route('admin.reports', ['tab' => 'customer', 'activity' => 'calls']))
            ->assertOk()
            ->assertSee('data-company-name="Called Party"', false)
            ->assertDontSee('data-company-name="Visited Party"', false)
            ->assertDontSee('data-company-name="Quoted Party"', false)
            ->assertDontSee('data-company-name="Silent Party"', false)
            ->assertSee('id="report-customer-visible-count">1<', false);
    }

    public function test_customer_report_activity_endpoint_returns_items_for_modal(): void
    {
        $admin = $this->admin();
        $company = $this->company(['name' => 'Modal Party']);
        Call::create([
            'company_id' => $company->id,
            'telecaller_id' => $this->telecaller('Modal TC')->id,
            'called_at' => now()->subHour(),
            'outcome' => 'Ringing',
            'remarks' => 'No answer yet',
        ]);

        $this->actingAs($admin)
            ->getJson(route('admin.reports.activity', [
                'company_id' => $company->id,
                'type' => 'calls',
            ]))
            ->assertOk()
            ->assertJsonPath('company', 'Modal Party')
            ->assertJsonPath('label', 'Calls')
            ->assertJsonPath('items.0.title', 'Ringing')
            ->assertJsonPath('items.0.detail', 'No answer yet');
    }
}
