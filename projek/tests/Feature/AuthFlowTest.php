<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_end_to_end_login_logout_with_seeded_super_admin(): void
    {
        $this->seed(DatabaseSeeder::class);

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => 'superadmin@example.com',
            'password' => 'password',
        ]);

        $login->assertStatus(200);
        $token = $login->json('data.token');
        $this->assertNotNull($token);

        $me = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/me');

        $me->assertStatus(200);
        $me->assertJsonPath('data.email', 'superadmin@example.com');

        $logout = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/logout');

        $logout->assertStatus(200);

        $this->app['auth']->forgetGuards();

        $afterLogout = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/me');

        $afterLogout->assertStatus(401);
    }
}
