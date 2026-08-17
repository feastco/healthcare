<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Demo accounts are provisioned only when the demo credential is
     * supplied through the DEMO_ADMIN_PASSWORD environment variable
     * (ADR-012). No plaintext credential is hard-coded here.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
        ]);

        $demoPassword = env('DEMO_ADMIN_PASSWORD');

        if (blank($demoPassword)) {
            return;
        }

        $superAdmin = Role::findByName('Super Admin');

        User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'password' => Hash::make($demoPassword),
        ])->assignRole($superAdmin);

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make($demoPassword),
        ]);
    }
}
