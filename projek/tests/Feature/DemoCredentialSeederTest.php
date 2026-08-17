<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DemoCredentialSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalDemoPassword = env('DEMO_ADMIN_PASSWORD');
    }

    protected function tearDown(): void
    {
        if ($this->originalDemoPassword === null) {
            putenv('DEMO_ADMIN_PASSWORD');
            unset($_ENV['DEMO_ADMIN_PASSWORD'], $_SERVER['DEMO_ADMIN_PASSWORD']);
        } else {
            putenv('DEMO_ADMIN_PASSWORD='.$this->originalDemoPassword);
            $_ENV['DEMO_ADMIN_PASSWORD'] = $this->originalDemoPassword;
            $_SERVER['DEMO_ADMIN_PASSWORD'] = $this->originalDemoPassword;
        }

        parent::tearDown();
    }

    public function test_demo_accounts_are_created_with_the_environment_credential(): void
    {
        putenv('DEMO_ADMIN_PASSWORD=env-demo-pass-123');
        $_ENV['DEMO_ADMIN_PASSWORD'] = 'env-demo-pass-123';
        $_SERVER['DEMO_ADMIN_PASSWORD'] = 'env-demo-pass-123';

        $this->seed(DatabaseSeeder::class);

        $superAdmin = User::where('email', 'superadmin@example.com')->first();
        $testUser = User::where('email', 'test@example.com')->first();

        $this->assertNotNull($superAdmin);
        $this->assertNotNull($testUser);
        $this->assertTrue(Hash::check('env-demo-pass-123', $superAdmin->password));
        $this->assertNotSame('env-demo-pass-123', $superAdmin->password);
        $this->assertTrue($superAdmin->hasRole('Super Admin'));
    }

    public function test_demo_accounts_are_skipped_when_the_environment_credential_is_absent(): void
    {
        putenv('DEMO_ADMIN_PASSWORD');
        unset($_ENV['DEMO_ADMIN_PASSWORD'], $_SERVER['DEMO_ADMIN_PASSWORD']);

        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseMissing('users', ['email' => 'superadmin@example.com']);
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }

    public function test_generic_factory_users_are_not_loginable_with_a_known_plaintext_password(): void
    {
        $user = User::factory()->create();

        $this->assertNotSame('password', $user->password);
        $this->assertFalse(Hash::check('password', $user->password));
    }
}
