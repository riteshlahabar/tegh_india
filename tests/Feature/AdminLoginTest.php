<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_admin_can_login_and_open_dashboard(): void
    {
        User::factory()->admin()->create([
            'username' => 'admin',
            'email' => 'admin@teghcables.com',
            'password' => '1234',
        ]);

        $this->post('/login', [
            'username' => 'admin',
            'password' => '1234',
        ])->assertRedirect('/');

        $this->get('/')->assertOk()->assertSee('TEGH CABLES INDIA');
    }

    public function test_telecaller_can_open_outstanding(): void
    {
        $user = User::factory()->create([
            'username' => 'aakanksha',
            'role' => 'telecaller',
            'password' => '1234',
        ]);

        $this->actingAs($user)
            ->get('/admin/outstanding')
            ->assertOk()
            ->assertSee('Outstanding');
    }
}
