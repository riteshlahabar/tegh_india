<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesCustomersApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_only_sees_customers_in_their_branch_city(): void
    {
        $sales = User::factory()->create([
            'role' => 'sales_executive',
            'branch' => 'Gujarat',
        ]);

        $gujarat = Company::create([
            'name' => 'Surat Cable House',
            'industry' => 'Electrical',
            'city' => 'Gujarat',
            'sub_city' => 'Surat',
            'address' => 'Ring Road',
            'credit_limit' => 0,
            'outstanding_amount' => 0,
            'stage' => 'New',
        ]);
        Company::create([
            'name' => 'Mumbai Wire Mart',
            'industry' => 'Electrical',
            'city' => 'Mumbai',
            'address' => 'Andheri',
            'credit_limit' => 0,
            'outstanding_amount' => 0,
            'stage' => 'New',
            'assigned_exec_id' => $sales->id,
        ]);

        $token = $sales->createToken('android')->plainTextToken;
        $response = $this->withToken($token)->getJson('/api/sales/customers');

        $response->assertOk();
        $response->assertJsonPath('total', 1);
        $response->assertJsonPath('branch', 'Gujarat');
        $response->assertJsonPath('customers.0.id', $gujarat->id);
        $response->assertJsonMissing(['name' => 'Mumbai Wire Mart']);
    }

    public function test_sales_customers_search_stays_inside_their_city(): void
    {
        $sales = User::factory()->create([
            'role' => 'sales_executive',
            'branch' => 'Mumbai',
        ]);
        Company::create([
            'name' => 'Alpha Wires',
            'industry' => 'Electrical',
            'city' => 'Mumbai',
            'address' => 'Andheri',
            'credit_limit' => 0,
            'outstanding_amount' => 0,
            'stage' => 'New',
        ]);
        Company::create([
            'name' => 'Beta Cables',
            'industry' => 'Electrical',
            'city' => 'Mumbai',
            'address' => 'Virar',
            'credit_limit' => 0,
            'outstanding_amount' => 0,
            'stage' => 'New',
        ]);
        Company::create([
            'name' => 'Beta Gujarat',
            'industry' => 'Electrical',
            'city' => 'Gujarat',
            'address' => 'Surat',
            'credit_limit' => 0,
            'outstanding_amount' => 0,
            'stage' => 'New',
        ]);

        $token = $sales->createToken('android')->plainTextToken;
        $response = $this->withToken($token)->getJson('/api/sales/customers?q=Beta');

        $response->assertOk();
        $response->assertJsonPath('total', 1);
        $response->assertJsonPath('customers.0.name', 'Beta Cables');
        $response->assertJsonMissing(['name' => 'Beta Gujarat']);
    }

    public function test_sales_can_claim_existing_customer_as_lead(): void
    {
        $sales = User::factory()->create([
            'role' => 'sales_executive',
            'branch' => 'Gujarat',
        ]);
        $company = Company::create([
            'name' => 'Navsari Wire House',
            'industry' => 'Electrical',
            'city' => 'Mumbai',
            'address' => 'Andheri',
            'credit_limit' => 0,
            'outstanding_amount' => 0,
            'stage' => 'New',
        ]);

        $token = $sales->createToken('android')->plainTextToken;
        $response = $this->withToken($token)->postJson('/api/sales/leads/claim', [
            'company_id' => $company->id,
        ]);

        $response->assertOk();
        $response->assertJsonPath('already_assigned', false);
        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'assigned_exec_id' => $sales->id,
        ]);

        $leads = $this->withToken($token)->getJson('/api/sales/leads');
        $leads->assertOk();
        $leads->assertJsonFragment(['name' => 'Navsari Wire House']);
    }

    public function test_sales_enquiry_options_and_full_company_create(): void
    {
        $sales = User::factory()->create([
            'name' => 'Priya Sales',
            'role' => 'sales_executive',
            'branch' => 'Mumbai',
        ]);
        $tc = User::factory()->create([
            'name' => 'Rina Telecaller',
            'role' => 'telecaller',
            'branch' => 'Mumbai',
        ]);
        $product = Product::create([
            'name' => '1.5 sqmm FR LF',
            'is_active' => true,
            'product_code' => 'FR15',
        ]);

        $token = $sales->createToken('android')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/sales/enquiry-options')
            ->assertOk()
            ->assertJsonPath('default_exec_id', $sales->id)
            ->assertJsonPath('default_tc_id', null)
            ->assertJsonPath('default_city', 'Mumbai')
            ->assertJsonFragment(['name' => 'Mumbai'])
            ->assertJsonFragment(['name' => 'Priya Sales'])
            ->assertJsonFragment(['name' => 'Rina Telecaller']);

        $this->withToken($token)->postJson('/api/sales/company/add', [
            'name' => 'Sales Full Enquiry Pvt',
            'contact_person_name' => 'Rajesh Sharma',
            'mobile_number' => '9876543210',
            'whatsapp_number' => '9876543210',
            'email' => 'contact@company.com',
            'industry' => 'Energy',
            'gst_number' => '27AAAAA0000A1Z5',
            'address' => 'Shop 1, Palaye Building',
            'latitude' => 19.0760,
            'longitude' => 72.8777,
            'city' => 'Mumbai',
            'credit_limit' => 50000,
            'payment_terms' => '30 Days',
            'assigned_exec_id' => $sales->id,
            'assigned_tc_id' => $tc->id,
            'stage' => 'Follow-up',
            'house_wire_discount' => true,
            'house_wire_discount_note' => '5% house',
            'product_ids' => [$product->id],
        ])
            ->assertCreated()
            ->assertJsonPath('company.gst_number', '27AAAAA0000A1Z5')
            ->assertJsonPath('company.payment_terms', '30 Days')
            ->assertJsonPath('company.stage', 'Follow-up')
            ->assertJsonPath('company.assigned_exec_id', $sales->id)
            ->assertJsonPath('company.assigned_tc_id', $tc->id)
            ->assertJsonPath('company.enquiry_product_name', '1.5 sqmm FR LF');

        $this->assertDatabaseHas('companies', [
            'name' => 'Sales Full Enquiry Pvt',
            'whatsapp_number' => '9876543210',
            'credit_limit' => 50000,
            'assigned_exec_id' => $sales->id,
            'assigned_tc_id' => $tc->id,
            'stage' => 'Follow-up',
        ]);
        $this->assertDatabaseHas('company_product', [
            'product_id' => $product->id,
        ]);
    }
}
