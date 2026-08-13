<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public const ROLES = [
        'Super Admin',
        'Registration Staff',
        'Doctor',
        'Cashier',
        'IT/Admin',
    ];

    public function run(): void
    {
        foreach (self::ROLES as $roleName) {
            Role::findOrCreate($roleName);
        }
    }
}
