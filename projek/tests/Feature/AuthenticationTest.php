<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleSeeder::class,
            PermissionSeeder::class,
        ]);
    }

    public function test_login_with_valid_credentials_returns_token(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'secret-password',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'secret-password',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'token',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'roles',
                    'permissions',
                ],
            ],
        ]);
        $this->assertNotNull($response->json('data.token'));
    }

    public function test_login_with_invalid_credentials_returns_401(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'secret-password',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors']);
    }

    public function test_logout_invalidates_current_token(): void
    {
        $user = User::factory()->create();

        $token = $user->createToken('auth-token');
        $this->assertDatabaseCount('personal_access_tokens', 1);

        $response = $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_protected_route_without_token_returns_401(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(401);
    }

    public function test_me_returns_authenticated_user_profile(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(200);
        $response->assertJsonPath('data.email', $user->email);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'email',
                'roles',
                'permissions',
            ],
        ]);
    }
}
