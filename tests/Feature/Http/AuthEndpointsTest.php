<?php

namespace Tests\Feature\Http;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pins the HTTP contract the frontend (welcome.blade.php) relies on:
 * status codes, JSON shape, and the register-time credit rules.
 */
class AuthEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_grants_three_credits_and_logs_in(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Taro',
            'email' => 'taro@example.com',
            'password' => 'secret123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('user.name', 'Taro')
            ->assertJsonPath('user.credits', 3)
            ->assertJsonStructure(['message', 'user' => ['id', 'name', 'credits']]);
        $this->assertAuthenticated();
    }

    public function test_register_rejects_disposable_email_domains(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'Spam',
            'email' => 'spam@mailinator.com',
            'password' => 'secret123',
        ])->assertStatus(422)->assertJsonStructure(['message']);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_second_signup_from_the_same_ip_gets_no_free_credits(): void
    {
        User::factory()->create(['signup_ip' => '127.0.0.1', 'credits' => 3]);

        $this->postJson('/api/auth/register', [
            'name' => 'Again',
            'email' => 'again@example.com',
            'password' => 'secret123',
        ])->assertCreated()->assertJsonPath('user.credits', 0);
    }

    public function test_second_signup_with_the_same_device_fingerprint_gets_no_free_credits(): void
    {
        User::factory()->create(['signup_ip' => '10.0.0.9', 'device_fingerprint' => 'fp-1', 'credits' => 3]);

        $this->postJson('/api/auth/register', [
            'name' => 'Again',
            'email' => 'again@example.com',
            'password' => 'secret123',
            'device_fingerprint' => 'fp-1',
        ])->assertCreated()->assertJsonPath('user.credits', 0);
    }

    public function test_login_succeeds_with_valid_credentials(): void
    {
        $user = User::factory()->create(['password' => 'secret123', 'credits' => 2]);

        $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'secret123'])
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.credits', 2);
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $user = User::factory()->create(['password' => 'secret123']);

        $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'wrong'])
            ->assertUnauthorized()
            ->assertJsonStructure(['message']);
        $this->assertGuest();
    }

    public function test_logout_ends_the_session(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/api/auth/logout')
            ->assertOk()
            ->assertJsonStructure(['message']);
        $this->assertGuest();
    }

    public function test_me_reports_guest(): void
    {
        $this->getJson('/api/auth/me')->assertOk()->assertExactJson(['logged_in' => false]);
    }

    public function test_me_reports_the_logged_in_user(): void
    {
        $user = User::factory()->create(['credits' => 5]);

        $this->actingAs($user)->getJson('/api/auth/me')
            ->assertOk()
            ->assertExactJson(['logged_in' => true, 'user' => ['id' => $user->id, 'name' => $user->name, 'credits' => 5]]);
    }
}
