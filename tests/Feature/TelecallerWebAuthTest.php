<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Company;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TelecallerWebAuthTest extends TestCase
{
    use RefreshDatabase;

    private function telecaller(): User
    {
        return User::factory()->create([
            'email' => 'tc@tpi.test',
            'password' => Hash::make('secret12'),
            'role' => 'telecaller',
            'branch' => 'Mumbai',
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create([
            'email' => 'admin@tpi.test',
            'password' => Hash::make('secret12'),
            'role' => 'admin',
            'branch' => 'All',
        ]);
    }

    public function test_telecaller_login_page_is_separate_from_admin(): void
    {
        $this->get('/login')->assertOk()->assertSee('Admin Login');
        $this->get('/telecaller/login')->assertOk()->assertSee('Inside sales login');
    }

    public function test_telecaller_can_login_to_web_portal(): void
    {
        $this->telecaller();

        $response = $this->post('/telecaller/login', [
            'email' => 'tc@tpi.test',
            'password' => 'secret12',
        ]);

        $response->assertRedirect(route('telecaller.dashboard'));
        $this->assertAuthenticated();
    }

    public function test_admin_cannot_use_telecaller_login(): void
    {
        $this->admin();

        $response = $this->from('/telecaller/login')->post('/telecaller/login', [
            'email' => 'admin@tpi.test',
            'password' => 'secret12',
        ]);

        $response->assertRedirect('/telecaller/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_telecaller_cannot_use_admin_login(): void
    {
        $this->telecaller();

        $response = $this->from('/login')->post('/login', [
            'email' => 'tc@tpi.test',
            'password' => 'secret12',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_telecaller_is_redirected_away_from_admin_pages(): void
    {
        $tc = $this->telecaller();

        $this->actingAs($tc)->get('/')->assertRedirect(route('telecaller.dashboard'));
        $this->actingAs($tc)->get(route('admin.leads'))->assertRedirect(route('telecaller.dashboard'));
    }

    public function test_telecaller_sees_only_assigned_leads(): void
    {
        $tc = $this->telecaller();
        $mine = Company::create([
            'name' => 'Mine Cables',
            'city' => 'Mumbai',
            'credit_limit' => 0,
            'outstanding_amount' => 0,
            'stage' => 'New',
            'assigned_tc_id' => $tc->id,
        ]);
        Company::create([
            'name' => 'Someone Else',
            'city' => 'Pune',
            'credit_limit' => 0,
            'outstanding_amount' => 0,
            'stage' => 'New',
        ]);

        $response = $this->actingAs($tc)->get(route('telecaller.leads'));
        $response->assertOk();
        $response->assertSee('Mine Cables');
        $response->assertDontSee('Someone Else');
        $this->actingAs($tc)->get(route('telecaller.leads.show', $mine->id))->assertOk();
    }

    public function test_telecaller_sees_unassigned_leads_in_their_branch(): void
    {
        $tc = $this->telecaller();
        Company::create([
            'name' => 'Open Mumbai Co',
            'city' => 'Mumbai',
            'credit_limit' => 0,
            'outstanding_amount' => 0,
            'stage' => 'New',
        ]);

        $response = $this->actingAs($tc)->get(route('telecaller.leads'));
        $response->assertOk();
        $response->assertSee('Open Mumbai Co');
    }

    public function test_telecaller_can_open_unassigned_branch_lead(): void
    {
        $tc = $this->telecaller();
        $open = Company::create([
            'name' => 'Open Desk Co',
            'city' => 'Mumbai',
            'credit_limit' => 0,
            'outstanding_amount' => 0,
            'stage' => 'New',
        ]);

        $this->actingAs($tc)->get(route('telecaller.leads.show', $open->id))->assertOk();
    }

    public function test_telecaller_can_log_call_for_assigned_lead(): void
    {
        $tc = $this->telecaller();
        $lead = Company::create([
            'name' => 'Call Me Ltd',
            'city' => 'Mumbai',
            'credit_limit' => 0,
            'outstanding_amount' => 0,
            'stage' => 'New',
            'assigned_tc_id' => $tc->id,
        ]);

        $response = $this->actingAs($tc)->post(route('telecaller.calls.store'), [
            'company_id' => $lead->id,
            'outcome' => 'Interested',
            'remarks' => 'Asked for catalogue',
            'next_followup' => now()->addDay()->toDateString(),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('calls', [
            'company_id' => $lead->id,
            'telecaller_id' => $tc->id,
            'outcome' => 'Interested',
        ]);
        $this->assertSame('Follow-up', $lead->fresh()->stage);
    }

    public function test_telecaller_web_accepts_profile_shared_and_no_requirement(): void
    {
        $tc = $this->telecaller();
        $shared = Company::create([
            'name' => 'Profile Co',
            'city' => 'Mumbai',
            'credit_limit' => 0,
            'outstanding_amount' => 0,
            'stage' => 'New',
            'assigned_tc_id' => $tc->id,
        ]);
        $none = Company::create([
            'name' => 'No Need Co',
            'city' => 'Mumbai',
            'credit_limit' => 0,
            'outstanding_amount' => 0,
            'stage' => 'New',
            'assigned_tc_id' => $tc->id,
        ]);

        $this->actingAs($tc)->post(route('telecaller.calls.store'), [
            'company_id' => $shared->id,
            'outcome' => 'Profile Shared',
        ])->assertRedirect();
        $this->actingAs($tc)->post(route('telecaller.calls.store'), [
            'company_id' => $none->id,
            'outcome' => 'No Requirement',
        ])->assertRedirect();

        $this->assertSame('Follow-up', $shared->fresh()->stage);
        $this->assertSame('Lost', $none->fresh()->stage);
    }

    public function test_telecaller_can_update_lead_stage_from_web(): void
    {
        $tc = $this->telecaller();
        $lead = Company::create([
            'name' => 'Stage Web Ltd',
            'city' => 'Mumbai',
            'credit_limit' => 0,
            'outstanding_amount' => 0,
            'stage' => 'New',
            'assigned_tc_id' => $tc->id,
        ]);

        $this->actingAs($tc)->post(route('telecaller.leads.stage', $lead->id), [
            'stage' => 'Won',
        ])->assertRedirect();

        $this->assertSame('Won', $lead->fresh()->stage);
    }

    public function test_telecaller_cannot_open_other_city_unassigned_lead(): void
    {
        $tc = $this->telecaller();
        $other = Company::create([
            'name' => 'Hidden Co',
            'city' => 'Pune',
            'credit_limit' => 0,
            'outstanding_amount' => 0,
            'stage' => 'New',
        ]);

        $this->actingAs($tc)->get(route('telecaller.leads.show', $other->id))->assertNotFound();
    }

    public function test_gujarat_telecaller_sees_surat_customers(): void
    {
        $tc = User::factory()->create([
            'role' => 'telecaller',
            'branch' => 'Gujarat',
        ]);
        Company::create([
            'name' => 'Surat Wire House',
            'city' => 'Surat',
            'credit_limit' => 0,
            'outstanding_amount' => 0,
            'stage' => 'New',
        ]);

        $this->actingAs($tc)->get(route('telecaller.leads'))
            ->assertOk()
            ->assertSee('Surat Wire House');
    }

    public function test_telecaller_can_create_quotation_from_web_portal(): void
    {
        $tc = $this->telecaller();
        $lead = Company::create([
            'name' => 'Web Quote Co',
            'city' => 'Mumbai',
            'credit_limit' => 0,
            'outstanding_amount' => 0,
            'stage' => 'New',
            'assigned_tc_id' => $tc->id,
        ]);
        $product = Product::create([
            'name' => '2.5 sqmm FRLS',
            'is_active' => true,
        ]);

        $this->actingAs($tc)->get(route('telecaller.quotations'))
            ->assertOk()
            ->assertSee('Create quotation')
            ->assertSee('Type name, city or mobile')
            ->assertSee('tc-customer-search', false)
            ->assertDontSee('Select customer');

        $this->actingAs($tc)->getJson(route('telecaller.companies.search', ['q' => 'Web Quote']))
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('companies.0.name', 'Web Quote Co');

        $this->actingAs($tc)->get(route('telecaller.products.search', ['q' => '2.5']))
            ->assertOk()
            ->assertJsonPath('products.0.name', '2.5 sqmm FRLS');

        $this->actingAs($tc)->post(route('telecaller.quotations.store'), [
            'company_id' => $lead->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'description' => $product->name,
                    'qty' => 10,
                    'list_price' => 25,
                    'discount_percent' => 0,
                ],
            ],
        ])->assertRedirect(route('telecaller.quotations'));

        $this->assertDatabaseHas('quotations', [
            'company_id' => $lead->id,
            'status' => 'Sent',
        ]);
        $this->assertSame('Quotation Sent', $lead->fresh()->stage);
    }

    public function test_telecaller_quotation_customer_search_is_limited_to_visible_leads(): void
    {
        $tc = $this->telecaller();
        $other = User::factory()->create([
            'role' => 'telecaller',
            'branch' => 'Pune',
        ]);
        Company::create([
            'name' => 'Mumbai Search Co',
            'city' => 'Mumbai',
            'mobile_number' => '9876500001',
            'credit_limit' => 0,
            'outstanding_amount' => 0,
            'stage' => 'New',
            'assigned_tc_id' => $tc->id,
        ]);
        Company::create([
            'name' => 'Other Desk Co',
            'city' => 'Pune',
            'credit_limit' => 0,
            'outstanding_amount' => 0,
            'stage' => 'New',
            'assigned_tc_id' => $other->id,
        ]);

        $this->actingAs($tc)
            ->getJson(route('telecaller.companies.search', ['q' => 'Search']))
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('companies.0.name', 'Mumbai Search Co')
            ->assertJsonMissing(['name' => 'Other Desk Co']);

        $this->actingAs($tc)
            ->get(route('telecaller.quotations', ['company_id' => Company::where('name', 'Mumbai Search Co')->value('id')]))
            ->assertOk()
            ->assertSee('Mumbai Search Co');
    }

    public function test_telecaller_can_add_customer_from_quotation_form(): void
    {
        $tc = $this->telecaller();
        $exec = User::factory()->create([
            'name' => 'Amit Sales',
            'role' => 'sales_executive',
            'branch' => 'Mumbai',
        ]);
        City::query()->firstOrCreate(['name' => 'Mumbai']);

        $this->actingAs($tc)->get(route('telecaller.quotations'))
            ->assertOk()
            ->assertSee('+ Add customer')
            ->assertSee('Company Name')
            ->assertSee('Enter corporate name')
            ->assertSee('Wire Discounts')
            ->assertSee('House Wire Discount')
            ->assertSee('Flexible Wire Discount')
            ->assertSee('Other Wire Discount')
            ->assertSee('Contact Person Name')
            ->assertSee('Mobile Number')
            ->assertSee('WhatsApp Number')
            ->assertSee('Email ID')
            ->assertSee('Industry Sector')
            ->assertSee('GST Number')
            ->assertSee('Office / Shipping Address')
            ->assertSee('Latitude (optional)')
            ->assertSee('Longitude (optional)')
            ->assertSee('City / Branch')
            ->assertSee('Sub City')
            ->assertSee('Credit Limit')
            ->assertSee('Payment Terms')
            ->assertSee('Assign Salesperson')
            ->assertSee('Assign Telecaller')
            ->assertSee('Initial Pipeline Stage');

        $response = $this->actingAs($tc)
            ->from(route('telecaller.quotations'))
            ->post(route('telecaller.companies.store'), [
                'name' => 'Quote Enquiry Pvt',
                'house_wire_discount' => '1',
                'house_wire_discount_note' => '5% extra on house wire',
                'flexible_wire_discount' => '1',
                'other_wire_discount_note' => 'Special copper rate',
                'contact_person_name' => 'Rajesh Sharma',
                'mobile_number' => '9876543210',
                'whatsapp_number' => '9876543210',
                'email' => 'contact@company.com',
                'industry' => 'Energy',
                'gst_number' => '27AAAAA0000A1Z5',
                'address' => 'Shop 1, Palaye Building',
                'latitude' => '19.0760',
                'longitude' => '72.8777',
                'city' => 'Mumbai',
                'credit_limit' => '0',
                'payment_terms' => '30 Days',
                'assigned_exec_id' => $exec->id,
                'assigned_tc_id' => $tc->id,
                'stage' => 'New',
            ]);

        $company = Company::where('name', 'Quote Enquiry Pvt')->first();
        $this->assertNotNull($company);
        $response->assertRedirect(route('telecaller.quotations', ['company_id' => $company->id]));

        $this->assertDatabaseHas('companies', [
            'name' => 'Quote Enquiry Pvt',
            'contact_person_name' => 'Rajesh Sharma',
            'mobile_number' => '9876543210',
            'whatsapp_number' => '9876543210',
            'email' => 'contact@company.com',
            'industry' => 'Energy',
            'gst_number' => '27AAAAA0000A1Z5',
            'city' => 'Mumbai',
            'credit_limit' => 0,
            'payment_terms' => '30 Days',
            'stage' => 'New',
            'assigned_exec_id' => $exec->id,
            'assigned_tc_id' => $tc->id,
            'created_by' => $tc->id,
            'house_wire_discount' => 1,
            'house_wire_discount_note' => '5% extra on house wire',
            'flexible_wire_discount' => 1,
            'other_wire_discount' => 0,
            'other_wire_discount_note' => 'Special copper rate',
        ]);

        $this->actingAs($tc)
            ->get(route('telecaller.quotations', ['company_id' => $company->id]))
            ->assertOk()
            ->assertSee('Quote Enquiry Pvt');
    }

    public function test_telecaller_customers_page_shows_assign_action(): void
    {
        $tc = $this->telecaller();
        Company::create([
            'name' => 'Assignable Cables',
            'city' => 'Mumbai',
            'credit_limit' => 0,
            'outstanding_amount' => 0,
            'stage' => 'New',
        ]);

        $this->actingAs($tc)
            ->get(route('telecaller.customers'))
            ->assertOk()
            ->assertSee('Assignable Cables')
            ->assertSee('Assign')
            ->assertSee('Assign Salesperson')
            ->assertSee('Assign Telecaller');
    }

    public function test_telecaller_can_assign_salesperson_and_telecaller(): void
    {
        $tc = $this->telecaller();
        $exec = User::factory()->create([
            'name' => 'Rahul Sales',
            'role' => 'sales_executive',
            'branch' => 'Mumbai',
        ]);
        $otherTc = User::factory()->create([
            'name' => 'Neha Telecaller',
            'role' => 'telecaller',
            'branch' => 'Mumbai',
        ]);
        $company = Company::create([
            'name' => 'Desk Assign Co',
            'city' => 'Nallasopara',
            'credit_limit' => 0,
            'outstanding_amount' => 0,
            'stage' => 'New',
        ]);

        $this->actingAs($tc)
            ->from(route('telecaller.customers'))
            ->post(route('telecaller.customers.assign', $company->id), [
                'assigned_exec_id' => $exec->id,
                'assigned_tc_id' => $otherTc->id,
            ])
            ->assertRedirect(route('telecaller.customers'));

        $company->refresh();
        $this->assertSame($exec->id, (int) $company->assigned_exec_id);
        $this->assertSame($otherTc->id, (int) $company->assigned_tc_id);
    }

    public function test_telecaller_cannot_assign_customer_outside_branch(): void
    {
        $tc = $this->telecaller();
        $exec = User::factory()->create([
            'role' => 'sales_executive',
            'branch' => 'Pune',
        ]);
        $company = Company::create([
            'name' => 'Pune Hidden Co',
            'city' => 'Pune',
            'credit_limit' => 0,
            'outstanding_amount' => 0,
            'stage' => 'New',
        ]);

        $this->actingAs($tc)
            ->post(route('telecaller.customers.assign', $company->id), [
                'assigned_exec_id' => $exec->id,
            ])
            ->assertNotFound();

        $this->assertNull($company->fresh()->assigned_exec_id);
    }
}
