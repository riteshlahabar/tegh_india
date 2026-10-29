<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProductSearchTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'branch' => 'All',
        ]);
    }

    public function test_admin_can_search_products_for_quotation_form(): void
    {
        $admin = $this->admin();
        Product::create([
            'name' => '2.5 sqmm FRLS House Wire',
            'product_code' => 'HW-2.5-FRLS',
            'hsn_code' => '85444920',
            'size_sqmm' => '2.5',
            'is_active' => true,
        ]);
        Product::create([
            'name' => '4 sqmm Flexible Cable',
            'product_code' => 'FX-4',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->getJson(route('admin.products.search', ['q' => '2.5']))
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('products.0.name', '2.5 sqmm FRLS House Wire')
            ->assertJsonPath('products.0.product_code', 'HW-2.5-FRLS');
    }

    public function test_quotations_page_uses_product_search_field(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.quotations'))
            ->assertOk()
            ->assertSee('Type to search products')
            ->assertSee(route('admin.products.search', [], false), false);
    }

    public function test_telecaller_cannot_use_admin_product_search(): void
    {
        $tc = User::factory()->create([
            'role' => 'telecaller',
            'branch' => 'Mumbai',
        ]);

        $this->actingAs($tc)
            ->getJson(route('admin.products.search', ['q' => 'wire']))
            ->assertRedirect();
    }
}
