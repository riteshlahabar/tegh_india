<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Company;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerDetailPageTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'branch' => 'All',
        ]);
    }

    private function company(array $overrides = []): Company
    {
        return Company::create(array_merge([
            'name' => 'Acme Cables',
            'contact_person_name' => 'Rajesh Sharma',
            'mobile_number' => '9876543210',
            'email' => 'rajesh@acme.test',
            'industry' => 'Industrial',
            'city' => 'Mumbai',
            'address' => 'Andheri East',
            'gst_number' => '27AAAAA0000A1Z5',
            'credit_limit' => 100000,
            'outstanding_amount' => 25000,
            'stage' => 'Quotation Sent',
            'payment_terms' => '30 Days',
        ], $overrides));
    }

    public function test_customers_list_links_company_name_to_detail_page(): void
    {
        $admin = $this->admin();
        $company = $this->company();

        $response = $this->actingAs($admin)->get(route('admin.customers'));

        $response->assertOk();
        $response->assertSee(route('admin.customers.show', $company->id), false);
        $response->assertSee('Acme Cables', false);
        $response->assertSee('customer-filter-search', false);
        $response->assertSee('Search company, contact, mobile, city, product, GST', false);
    }

    public function test_dashboard_city_chips_only_show_main_cities(): void
    {
        $admin = $this->admin();
        City::query()->firstOrCreate(['name' => 'Mumbai']);
        City::query()->firstOrCreate(['name' => 'Pune']);
        City::query()->firstOrCreate(['name' => 'Gujarat']);
        City::query()->firstOrCreate(['name' => 'Silvassa']);
        City::query()->firstOrCreate(['name' => 'Kalyan']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('data-branch="Mumbai"', false);
        $response->assertSee('data-branch="Pune"', false);
        $response->assertSee('data-branch="Gujarat"', false);
        $response->assertDontSee('data-branch="Silvassa"', false);
        $response->assertDontSee('data-branch="Kalyan"', false);
    }

    public function test_customers_list_paginates_results(): void
    {
        $admin = $this->admin();
        foreach (range(1, 3) as $i) {
            $this->company([
                'name' => sprintf('Customer %02d Ltd', $i),
                'email' => "c{$i}@example.test",
            ]);
        }

        $page1 = $this->actingAs($admin)->get(route('admin.customers', ['per_page' => 2]));
        $page1->assertOk();
        $page1->assertSee('Customer 01 Ltd', false);
        $page1->assertSee('Customer 02 Ltd', false);
        $page1->assertDontSee('Customer 03 Ltd', false);
        $page1->assertSee('Showing 1–2 of 3', false);

        $page2 = $this->actingAs($admin)->get(route('admin.customers', ['per_page' => 2, 'page' => 2]));
        $page2->assertOk();
        $page2->assertSee('Customer 03 Ltd', false);
        $page2->assertDontSee('Customer 01 Ltd', false);
        $page2->assertSee('Showing 3–3 of 3', false);
    }

    public function test_customers_list_city_filter_is_kept_on_page_two(): void
    {
        $admin = $this->admin();
        foreach (range(1, 3) as $i) {
            $this->company([
                'name' => sprintf('Gujarat Co %02d', $i),
                'city' => 'Gujarat',
                'email' => "g{$i}@example.test",
            ]);
        }
        $this->company([
            'name' => 'Mumbai Co',
            'city' => 'Mumbai',
            'email' => 'mumbai@example.test',
        ]);

        $page1 = $this->actingAs($admin)->get(route('admin.customers', [
            'city' => 'Gujarat',
            'per_page' => 2,
        ]));
        $page1->assertOk();
        $page1->assertSee('Gujarat Co 01', false);
        $page1->assertSee('Gujarat Co 02', false);
        $page1->assertDontSee('Gujarat Co 03', false);
        $page1->assertDontSee('Mumbai Co', false);
        $page1->assertSee('city=Gujarat', false);
        $page1->assertSee('data-branch="Gujarat"', false);

        $page2 = $this->actingAs($admin)->get(route('admin.customers', [
            'city' => 'Gujarat',
            'per_page' => 2,
            'page' => 2,
        ]));
        $page2->assertOk();
        $page2->assertSee('Gujarat Co 03', false);
        $page2->assertDontSee('Gujarat Co 01', false);
        $page2->assertDontSee('Mumbai Co', false);
        $page2->assertSee('city=Gujarat', false);
    }

    public function test_customers_list_search_filters_across_pages(): void
    {
        $admin = $this->admin();
        $this->company(['name' => 'Acme Cables', 'email' => 'acme@example.test']);
        $this->company(['name' => 'Zenith Wires', 'email' => 'zenith@example.test']);

        $response = $this->actingAs($admin)->get(route('admin.customers', ['q' => 'Zenith']));

        $response->assertOk();
        $response->assertSee('Zenith Wires', false);
        $response->assertDontSee('Acme Cables', false);
    }

    public function test_customers_list_can_filter_with_and_without_phone_number(): void
    {
        $admin = $this->admin();
        $this->company([
            'name' => 'Has Mobile Co',
            'mobile_number' => '9403178429',
            'whatsapp_number' => null,
            'email' => 'has@example.test',
        ]);
        $this->company([
            'name' => 'WhatsApp Only Co',
            'mobile_number' => null,
            'whatsapp_number' => '9876500000',
            'email' => 'wa@example.test',
        ]);
        $this->company([
            'name' => 'No Number Co',
            'mobile_number' => null,
            'whatsapp_number' => null,
            'contact_person_name' => 'Missing Phone',
            'email' => 'none@example.test',
        ]);
        $this->company([
            'name' => 'Blank Number Co',
            'mobile_number' => '   ',
            'whatsapp_number' => '',
            'email' => 'blank@example.test',
        ]);

        $with = $this->actingAs($admin)->get(route('admin.customers', ['number' => 'with']));
        $with->assertOk();
        $with->assertSee('Has Mobile Co', false);
        $with->assertSee('WhatsApp Only Co', false);
        $with->assertDontSee('No Number Co', false);
        $with->assertDontSee('Blank Number Co', false);
        $with->assertSee('number=with', false);
        $with->assertSee('With number', false);

        $without = $this->actingAs($admin)->get(route('admin.customers', ['number' => 'without']));
        $without->assertOk();
        $without->assertSee('No Number Co', false);
        $without->assertSee('Blank Number Co', false);
        $without->assertDontSee('Has Mobile Co', false);
        $without->assertDontSee('WhatsApp Only Co', false);
        $without->assertSee('number=without', false);
    }

    public function test_admin_can_view_customer_full_details_and_sent_amounts(): void
    {
        $admin = $this->admin();
        $company = $this->company();

        Quotation::create([
            'company_id' => $company->id,
            'quotation_number' => 'Q-0001/26-27',
            'amount' => 40000,
            'status' => 'Sent',
            'date' => now()->subDays(3),
            'emailed_at' => now()->subDays(2),
            'emailed_to' => 'rajesh@acme.test',
        ]);

        Quotation::create([
            'company_id' => $company->id,
            'quotation_number' => 'Q-0002/26-27',
            'amount' => 15000,
            'status' => 'Approved',
            'date' => now()->subDay(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.customers.show', $company->id));

        $response->assertOk();
        $response->assertSee('Acme Cables', false);
        $response->assertSee('Rajesh Sharma', false);
        $response->assertSee('9876543210', false);
        $response->assertSee('Q-0001/26-27', false);
        $response->assertSee('Q-0002/26-27', false);
        $response->assertSee('40,000', false);
        $response->assertSee('55,000', false);
        $response->assertSee('Edit Customer', false);
        $response->assertSee('Delete Customer', false);
    }

    public function test_admin_can_update_customer(): void
    {
        $admin = $this->admin();
        $company = $this->company();
        City::query()->firstOrCreate(['name' => 'Pune']);

        $response = $this->actingAs($admin)->post(route('admin.companies.update', $company->id), [
            'name' => 'Acme Cables Pvt Ltd',
            'contact_person_name' => 'Anita Desai',
            'mobile_number' => '9123456789',
            'email' => 'anita@acme.test',
            'city' => 'Pune',
            'credit_limit' => 200000,
            'outstanding_amount' => 10000,
            'stage' => 'Negotiation',
            'payment_terms' => '45 Days',
        ]);

        $response->assertRedirect(route('admin.customers.show', $company->id));
        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'name' => 'Acme Cables Pvt Ltd',
            'contact_person_name' => 'Anita Desai',
            'city' => 'Pune',
            'stage' => 'Negotiation',
        ]);
    }

    public function test_admin_can_delete_customer(): void
    {
        $admin = $this->admin();
        $company = $this->company();

        $response = $this->actingAs($admin)->post(route('admin.companies.destroy', $company->id));

        $response->assertRedirect(route('admin.customers'));
        $this->assertDatabaseMissing('companies', ['id' => $company->id]);
    }

    public function test_admin_can_log_the_new_call_outcomes(): void
    {
        $admin = $this->admin();
        $company = $this->company(['stage' => 'New']);

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Profile Shared')
            ->assertSee('Follow-Up')
            ->assertSee('Not Interested')
            ->assertSee('No Requirement')
            ->assertSee('Call Back')
            ->assertSee('Ringing')
            ->assertSee('Regular Party');

        $this->actingAs($admin)->post(route('admin.calls.store'), [
            'company_id' => $company->id,
            'outcome' => 'Profile Shared',
            'remarks' => 'Catalogue sent on WhatsApp',
        ])->assertRedirect();

        $this->assertDatabaseHas('calls', [
            'company_id' => $company->id,
            'outcome' => 'Profile Shared',
        ]);
        $this->assertSame('Follow-up', $company->fresh()->stage);
    }
}
