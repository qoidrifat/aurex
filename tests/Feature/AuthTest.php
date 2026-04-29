<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Ken',
            'email' => 'ken@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertDatabaseHas('users', ['email' => 'ken@example.com']);
        $this->assertAuthenticated();
    }

    public function test_users_can_login(): void
    {
        $user = User::factory()->create(['email' => 'login@example.com']);

        $this->post('/login', [
            'email' => 'login@example.com',
            'password' => 'password',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post('/logout')->assertRedirect('/');

        $this->assertGuest();
    }

    public function test_authenticated_user_sees_dashboard(): void
    {
        $user = User::factory()->create(['name' => 'Qoid']);

        $this->actingAs($user)->get('/dashboard')
            ->assertStatus(200)
            ->assertSeeText('Qoid');
    }
}
