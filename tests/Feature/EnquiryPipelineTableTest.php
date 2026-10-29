<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnquiryPipelineTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_pipeline_view_renders_table_not_kanban(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'branch' => 'All',
        ]);

        Company::create([
            'name' => 'Acme Cables',
            'industry' => 'Industrial',
            'city' => 'Mumbai',
            'address' => 'Andheri',
            'credit_limit' => 100000,
            'outstanding_amount' => 50000,
            'stage' => 'New',
            'assigned_exec_id' => null,
            'assigned_tc_id' => null,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.leads'));

        $response->assertOk();
        $response->assertSee('id="view-pipeline"', false);
        $response->assertSee('Leads', false);
        $response->assertSee('Customer Name', false);
        $response->assertSee('Deal Value', false);
        $response->assertSee('pipeline-filter-search', false);
        $response->assertSee('Acme Cables', false);
        $response->assertSee('₹50,000', false);

        $response->assertDontSee('Enquiry Pipeline', false);
        $response->assertDontSee('Enquiry Pipeline Board', false);
        $response->assertDontSee('FOLLOW-UP (', false);
        $response->assertDontSee('partials.pipeline-card', false);
    }
}
