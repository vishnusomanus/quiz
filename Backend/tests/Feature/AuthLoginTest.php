<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\ClientRepository;
use Tests\TestCase;

class AuthLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! file_exists(storage_path('oauth-private.key')) || ! file_exists(storage_path('oauth-public.key'))) {
            $this->artisan('passport:keys', ['--no-interaction' => true]);
        }

        app(ClientRepository::class)->createPersonalAccessGrantClient(
            'Test Personal Access Client',
            'users',
        );
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ])
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('message', 'Login successful')
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.email', 'admin@example.com')
            ->assertJsonPath('data.user.role', 'admin')
            ->assertJsonPath('data.user.id', $user->uuid)
            ->assertJsonPath('data.user.uuid', $user->uuid)
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'token_type',
                    'expires_in',
                    'user' => ['id', 'uuid', 'name', 'email', 'role', 'country'],
                ],
            ]);
    }

    public function test_customer_can_login_and_access_profile(): void
    {
        $user = User::factory()->customer('IN')->create([
            'email' => 'customer@example.com',
            'password' => 'password',
        ]);

        $token = $this->postJson('/api/v1/auth/login', [
            'email' => 'customer@example.com',
            'password' => 'password',
        ])
            ->assertOk()
            ->json('data.token');

        $this->getJson('/api/v1/user', [
            'Authorization' => 'Bearer '.$token,
        ])
            ->assertOk()
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonPath('data.role', 'customer');
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ])
            ->assertStatus(422)
            ->assertJsonPath('status', false)
            ->assertJsonPath('message', 'Login failed');
    }

    public function test_login_requires_email_and_password(): void
    {
        $this->postJson('/api/v1/auth/login', [])
            ->assertStatus(422)
            ->assertJsonPath('status', false)
            ->assertJsonPath('message', 'Validation failed');
    }
}
