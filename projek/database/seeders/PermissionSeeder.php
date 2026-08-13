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
        'patients.view',
        'patients.create',
        'patients.update',
        'doctors.view',
        'doctors.create',
        'doctors.update',
        'doctors.delete',
        'departments.view',
        'departments.create',
        'departments.update',
        'departments.delete',
        'schedules.view',
        'schedules.create',
        'schedules.update',
        'schedules.delete',
        'appointments.view',
        'appointments.create',
    ];

    private const ROLE_PERMISSIONS = [
        'Registration Staff' => [
            'patients.view',
            'patients.create',
            'patients.update',
            'doctors.view',
            'departments.view',
            'schedules.view',
            'appointments.view',
            'appointments.create',
        ],
        'Doctor' => [
            'schedules.view',
        ],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permissionName) {
            Permission::findOrCreate($permissionName);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::findByName('Super Admin')->syncPermissions(self::PERMISSIONS);

        foreach (self::ROLE_PERMISSIONS as $roleName => $permissions) {
            Role::findByName($roleName)->syncPermissions($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
