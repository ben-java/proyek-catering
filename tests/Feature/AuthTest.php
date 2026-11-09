<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_guest_can_reach_login_page(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_admin_can_login_and_access_dashboard(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password')]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
        $this->get('/dashboard')->assertOk();
    }

    public function test_login_rejects_wrong_password(): void
    {
        User::factory()->create(['email' => 'a@b.test', 'password' => bcrypt('pass')]);

        $this->post('/login', ['email' => 'a@b.test', 'password' => 'salah'])
            ->assertSessionHasErrors('email');
    }

    public function test_login_accepts_nonemail_identifier(): void
    {
        User::factory()->create(['email' => 'admin', 'password' => bcrypt('password')]);

        $this->post('/login', ['email' => 'admin', 'password' => 'password'])
            ->assertRedirect('/dashboard');

        $this->assertAuthenticated();
    }
}
