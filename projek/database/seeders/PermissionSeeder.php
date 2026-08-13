<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public const PERMISSIONS = [
        'users.view',
        'users.create',
        'users.update',
        'roles.view',
        'roles.create',
        'roles.update',
        'roles.delete',
        'permissions.view',
        'roles.assign-permissions',
        'roles.revoke-permissions',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permissionName) {
            Permission::findOrCreate($permissionName);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $superAdmin = Role::findByName('Super Admin');
        $superAdmin->syncPermissions(self::PERMISSIONS);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
