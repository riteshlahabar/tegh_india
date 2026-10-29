<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminQuotationsFilterTest extends TestCase
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
            'name' => 'Filter Co',
            'city' => 'Mumbai',
            'credit_limit' => 0,
            'outstanding_amount' => 0,
            'stage' => 'Quotation Sent',
        ], $overrides));
    }

    private function quote(Company $company, array $overrides = []): Quotation
    {
        static $n = 1;

        return Quotation::create(array_merge([
            'company_id' => $company->id,
            'quotation_number' => 'Q-'.str_pad((string) $n++, 4, '0', STR_PAD_LEFT).'/26-27',
            'amount' => 10000,
            'status' => 'Sent',
            'date' => now(),
        ], $overrides));
    }

    public function test_quotations_page_shows_filter_controls(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.quotations'))
            ->assertOk()
            ->assertSee('name="q"', false)
            ->assertSee('name="company_id"', false)
            ->assertSee('name="salesman_id"', false)
            ->assertSee('name="telecaller_id"', false)
            ->assertSee('name="status"', false)
            ->assertSee('name="city"', false)
            ->assertSee('name="from"', false)
            ->assertSee('name="to"', false)
            ->assertSee('Quote status', false);
    }

    public function test_quotations_can_be_filtered_by_status_and_search(): void
    {
        $admin = $this->admin();
        $company = $this->company(['name' => 'Acme Cables']);
        $this->quote($company, ['quotation_number' => 'Q-1001/26-27', 'status' => 'Sent']);
        $this->quote($company, ['quotation_number' => 'Q-1002/26-27', 'status' => 'Approved']);

        $this->actingAs($admin)
            ->get(route('admin.quotations', ['status' => 'Approved']))
            ->assertOk()
            ->assertSee('Q-1002/26-27')
            ->assertDontSee('Q-1001/26-27');

        $this->actingAs($admin)
            ->get(route('admin.quotations', ['q' => 'Q-1001']))
            ->assertOk()
            ->assertSee('Q-1001/26-27')
            ->assertDontSee('Q-1002/26-27');
    }

    public function test_quotations_can_be_filtered_by_salesman_and_date(): void
    {
        $admin = $this->admin();
        $sales = User::factory()->create([
            'name' => 'Rahul Sales',
            'role' => 'sales_executive',
            'branch' => 'Mumbai',
        ]);
        $mine = $this->company([
            'name' => 'Mine Quotes Ltd',
            'assigned_exec_id' => $sales->id,
        ]);
        $other = $this->company(['name' => 'Other Quotes Ltd']);
        $this->quote($mine, [
            'quotation_number' => 'Q-2001/26-27',
            'date' => '2026-08-10 10:00:00',
        ]);
        $this->quote($other, [
            'quotation_number' => 'Q-2002/26-27',
            'date' => '2026-08-20 10:00:00',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.quotations', ['salesman_id' => $sales->id]))
            ->assertOk()
            ->assertSee('Q-2001/26-27')
            ->assertDontSee('Q-2002/26-27');

        $this->actingAs($admin)
            ->get(route('admin.quotations', ['from' => '2026-08-15']))
            ->assertOk()
            ->assertSee('Q-2002/26-27')
            ->assertDontSee('Q-2001/26-27');
    }
}
