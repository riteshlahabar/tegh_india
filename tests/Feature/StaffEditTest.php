<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StaffEditTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'branch' => 'All',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        City::query()->firstOrCreate(['name' => 'Mumbai']);
        City::query()->firstOrCreate(['name' => 'Pune']);
    }

    public function test_staff_page_shows_edit_action(): void
    {
        $admin = $this->admin();
        $sales = User::factory()->create([
            'name' => 'Amit Patil',
            'role' => 'sales_executive',
            'branch' => 'Mumbai',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.staff'));

        $response->assertOk();
        $response->assertSee('js-edit-staff', false);
        $response->assertSee('Amit Patil', false);
        $response->assertSee('modal-edit-staff', false);
        $response->assertSee((string) $sales->id, false);
    }

    public function test_admin_can_update_staff_profile_without_changing_password(): void
    {
        $admin = $this->admin();
        $sales = User::factory()->create([
            'name' => 'Amit Patil',
            'email' => 'amit@tpi.test',
            'password' => Hash::make('old-pass-1'),
            'role' => 'sales_executive',
            'branch' => 'Mumbai',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.users.update', $sales->id), [
            'name' => 'Amit P.',
            'email' => 'amit.p@tpi.test',
            'role' => 'telecaller',
            'branch' => 'Pune',
            'password' => '',
        ]);

        $response->assertRedirect();
        $sales->refresh();
        $this->assertSame('Amit P.', $sales->name);
        $this->assertSame('amit.p@tpi.test', $sales->email);
        $this->assertSame('telecaller', $sales->role);
        $this->assertSame('Pune', $sales->branch);
        $this->assertTrue(Hash::check('old-pass-1', $sales->password));
    }

    public function test_admin_can_reset_staff_password(): void
    {
        $admin = $this->admin();
        $sales = User::factory()->create([
            'email' => 'sales@tpi.test',
            'password' => Hash::make('old-pass-1'),
            'role' => 'sales_executive',
            'branch' => 'Mumbai',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.users.update', $sales->id), [
            'name' => $sales->name,
            'email' => $sales->email,
            'role' => 'sales_executive',
            'branch' => 'Mumbai',
            'password' => 'new-pass-9',
        ]);

        $response->assertRedirect();
        $sales->refresh();
        $this->assertTrue(Hash::check('new-pass-9', $sales->password));
        $this->assertFalse(Hash::check('old-pass-1', $sales->password));
    }

    public function test_cannot_demote_the_last_admin(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->from(route('admin.staff'))->post(route('admin.users.update', $admin->id), [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => 'sales_executive',
            'branch' => 'Mumbai',
        ]);

        $response->assertRedirect(route('admin.staff'));
        $response->assertSessionHas('error');
        $this->assertSame('admin', $admin->fresh()->role);
    }
}
