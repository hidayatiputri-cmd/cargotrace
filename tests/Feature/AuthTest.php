<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_login_page_renders_successfully(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('CargoTrace Access Portal');
    }

    public function test_user_can_authenticate_via_web(): void
    {
        $user = User::factory()->create([
            'email' => 'operator@cargotrace.test',
            'password' => bcrypt('password'),
            'role' => User::ROLE_OPERATOR,
        ]);

        $response = $this->post('/login', [
            'email' => 'operator@cargotrace.test',
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }
}
