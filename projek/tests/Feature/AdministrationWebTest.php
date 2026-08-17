<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdministrationWebTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    private function userWithRole(string $role): User
    {
        return User::factory()->create()->assignRole($role);
    }

    private function superAdmin(): User
    {
        return $this->userWithRole('Super Admin');
    }

    private function createPermission(string $name): Permission
    {
        return Permission::create(['name' => $name]);
    }

    public function test_guest_is_redirected_to_login_when_accessing_users(): void
    {
        $this->get('/administration/users')->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_to_login_when_accessing_roles(): void
    {
        $this->get('/administration/roles')->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_to_login_when_accessing_permission_assignment(): void
    {
        $this->get('/administration/permissions')->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_to_login_when_creating_user(): void
    {
        $this->get('/administration/users/create')->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_to_login_when_editing_role(): void
    {
        $this->get('/administration/roles/1/edit')->assertRedirect(route('login'));
    }

    public function test_registration_staff_cannot_access_users(): void
    {
        $this->actingAs($this->userWithRole('Registration Staff'))
            ->get('/administration/users')
            ->assertStatus(403);
    }

    public function test_registration_staff_cannot_access_roles(): void
    {
        $this->actingAs($this->userWithRole('Registration Staff'))
            ->get('/administration/roles')
            ->assertStatus(403);
    }

    public function test_registration_staff_cannot_access_permission_assignment(): void
    {
        $this->actingAs($this->userWithRole('Registration Staff'))
            ->get('/administration/permissions')
            ->assertStatus(403);
    }

    public function test_doctor_cannot_access_users(): void
    {
        $this->actingAs($this->userWithRole('Doctor'))
            ->get('/administration/users')
            ->assertStatus(403);
    }

    public function test_cashier_cannot_access_users(): void
    {
        $this->actingAs($this->userWithRole('Cashier'))
            ->get('/administration/users')
            ->assertStatus(403);
    }

    public function test_it_admin_cannot_access_users(): void
    {
        $this->actingAs($this->userWithRole('IT/Admin'))
            ->get('/administration/users')
            ->assertStatus(403);
    }

    public function test_cashier_cannot_access_permission_assignment(): void
    {
        $this->actingAs($this->userWithRole('Cashier'))
            ->get('/administration/permissions')
            ->assertStatus(403);
    }

    public function test_user_without_role_cannot_access_roles(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/administration/roles')
            ->assertStatus(403);
    }

    public function test_super_admin_can_access_users_index(): void
    {
        $this->actingAs($this->superAdmin())
            ->get('/administration/users')
            ->assertOk()
            ->assertSee('Users')
            ->assertSee('Add User');
    }

    public function test_super_admin_can_access_user_create_form(): void
    {
        $this->actingAs($this->superAdmin())
            ->get('/administration/users/create')
            ->assertOk()
            ->assertSee('Full Name')
            ->assertSee('Password')
            ->assertSee('Roles');
    }

    public function test_super_admin_can_create_user_with_roles(): void
    {
        $doctorRole = Role::findByName('Doctor');

        $this->actingAs($this->superAdmin())
            ->post('/administration/users', [
                'name' => 'Nurse Nadia',
                'email' => 'nadia@example.com',
                'password' => 'secret-password',
                'roles' => [$doctorRole->id],
            ])
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', ['email' => 'nadia@example.com']);

        $user = User::where('email', 'nadia@example.com')->first();
        $this->assertTrue(Hash::check('secret-password', $user->password));
        $this->assertTrue($user->hasRole('Doctor'));
    }

    public function test_user_create_requires_email(): void
    {
        $this->actingAs($this->superAdmin())
            ->post('/administration/users', [
                'name' => 'No Email',
                'password' => 'secret-password',
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_super_admin_can_edit_user(): void
    {
        $user = $this->userWithRole('Cashier');

        $this->actingAs($this->superAdmin())
            ->get("/administration/users/{$user->id}/edit")
            ->assertOk()
            ->assertSee($user->name)
            ->assertSee($user->email);
    }

    public function test_super_admin_can_update_user(): void
    {
        $user = $this->userWithRole('Cashier');
        $doctorRole = Role::findByName('Doctor');

        $this->actingAs($this->superAdmin())
            ->put("/administration/users/{$user->id}", [
                'name' => 'Updated Name',
                'email' => $user->email,
                'roles' => [$doctorRole->id],
            ])
            ->assertRedirect(route('users.index'));

        $user->refresh();
        $this->assertSame('Updated Name', $user->name);
        $this->assertTrue($user->hasRole('Doctor'));
        $this->assertFalse($user->hasRole('Cashier'));
    }

    public function test_user_update_preserves_password_when_blank(): void
    {
        $user = $this->userWithRole('Cashier');
        $originalHash = $user->password;

        $this->actingAs($this->superAdmin())
            ->put("/administration/users/{$user->id}", [
                'name' => $user->name,
                'email' => $user->email,
            ])
            ->assertRedirect(route('users.index'));

        $this->assertSame($originalHash, $user->fresh()->password);
    }

    public function test_user_update_can_change_password(): void
    {
        $user = $this->userWithRole('Cashier');

        $this->actingAs($this->superAdmin())
            ->put("/administration/users/{$user->id}", [
                'name' => $user->name,
                'email' => $user->email,
                'password' => 'new-secret-password',
            ])
            ->assertRedirect(route('users.index'));

        $this->assertTrue(Hash::check('new-secret-password', $user->fresh()->password));
    }

    public function test_users_have_no_delete_route(): void
    {
        $user = $this->userWithRole('Cashier');

        $this->actingAs($this->superAdmin())
            ->delete("/administration/users/{$user->id}")
            ->assertStatus(405);

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_super_admin_can_access_roles_index(): void
    {
        $this->actingAs($this->superAdmin())
            ->get('/administration/roles')
            ->assertOk()
            ->assertSee('Roles')
            ->assertSee('Add Role');
    }

    public function test_super_admin_can_access_role_create_form(): void
    {
        $this->actingAs($this->superAdmin())
            ->get('/administration/roles/create')
            ->assertOk()
            ->assertSee('Role Name')
            ->assertSee('Permissions');
    }

    public function test_super_admin_can_create_role_with_permissions(): void
    {
        $permission = $this->createPermission('billing.report');

        $this->actingAs($this->superAdmin())
            ->post('/administration/roles', [
                'name' => 'Billing Clerk',
                'permissions' => [$permission->id],
            ])
            ->assertRedirect(route('roles.index'));

        $role = Role::findByName('Billing Clerk');
        $this->assertTrue($role->hasPermissionTo('billing.report'));
    }

    public function test_super_admin_can_edit_role(): void
    {
        $role = Role::findByName('Cashier');

        $this->actingAs($this->superAdmin())
            ->get("/administration/roles/{$role->id}/edit")
            ->assertOk()
            ->assertSee('Cashier');
    }

    public function test_super_admin_can_update_role(): void
    {
        $role = Role::findByName('Cashier');
        $permission = $this->createPermission('billing.report');

        $this->actingAs($this->superAdmin())
            ->put("/administration/roles/{$role->id}", [
                'name' => 'Senior Cashier',
                'permissions' => [$permission->id],
            ])
            ->assertRedirect(route('roles.index'));

        $role->refresh();
        $this->assertSame('Senior Cashier', $role->name);
        $this->assertTrue($role->hasPermissionTo('billing.report'));
    }

    public function test_super_admin_can_delete_role_without_users(): void
    {
        $role = Role::create(['name' => 'Intern']);

        $this->actingAs($this->superAdmin())
            ->delete("/administration/roles/{$role->id}")
            ->assertRedirect(route('roles.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    public function test_role_with_users_cannot_be_deleted(): void
    {
        $user = $this->userWithRole('Cashier');
        $role = Role::findByName('Cashier');

        $this->actingAs($this->superAdmin())
            ->from(route('roles.index'))
            ->delete("/administration/roles/{$role->id}")
            ->assertRedirect(route('roles.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('roles', ['id' => $role->id]);
        $this->assertTrue($user->hasRole('Cashier'));
    }

    public function test_role_delete_button_is_disabled_when_role_has_users(): void
    {
        $this->userWithRole('Cashier');

        $this->actingAs($this->superAdmin())
            ->get('/administration/roles')
            ->assertOk()
            ->assertSee('Role has user assignments')
            ->assertSee('disabled');
    }

    public function test_role_delete_button_is_available_when_role_has_no_users(): void
    {
        $role = Role::create(['name' => 'Intern']);

        $this->actingAs($this->superAdmin())
            ->get('/administration/roles')
            ->assertOk()
            ->assertSee(route('roles.destroy', $role), false);
    }

    public function test_super_admin_can_access_permission_assignment_index(): void
    {
        $this->actingAs($this->superAdmin())
            ->get('/administration/permissions')
            ->assertOk()
            ->assertSee('Permission Assignment')
            ->assertSee('Manage Permissions');
    }

    public function test_super_admin_can_access_permission_assignment_form(): void
    {
        $role = Role::findByName('Cashier');

        $this->actingAs($this->superAdmin())
            ->get("/administration/permissions/{$role->id}/edit")
            ->assertOk()
            ->assertSee('Manage Permissions')
            ->assertSee('Save Permissions');
    }

    public function test_super_admin_can_grant_permissions_to_role(): void
    {
        $role = Role::findByName('Cashier');
        $permissionA = $this->createPermission('billing.report');
        $permissionB = $this->createPermission('billing.refund');

        $this->actingAs($this->superAdmin())
            ->put("/administration/permissions/{$role->id}", [
                'permissions' => [$permissionA->id, $permissionB->id],
            ])
            ->assertRedirect(route('permissions.index'));

        $role->refresh();
        $this->assertTrue($role->hasPermissionTo('billing.report'));
        $this->assertTrue($role->hasPermissionTo('billing.refund'));
    }

    public function test_super_admin_can_revoke_permission_from_role(): void
    {
        $role = Role::findByName('Cashier');
        $permissionA = $this->createPermission('billing.report');
        $permissionB = $this->createPermission('billing.refund');
        $role->givePermissionTo([$permissionA, $permissionB]);

        $this->actingAs($this->superAdmin())
            ->put("/administration/permissions/{$role->id}", [
                'permissions' => [$permissionA->id],
            ])
            ->assertRedirect(route('permissions.index'));

        $role->refresh();
        $this->assertTrue($role->hasPermissionTo('billing.report'));
        $this->assertFalse($role->hasPermissionTo('billing.refund'));
    }

    public function test_super_admin_can_revoke_all_permissions_from_role(): void
    {
        $role = Role::findByName('Cashier');
        $permissionA = $this->createPermission('billing.report');
        $role->givePermissionTo([$permissionA]);

        $this->actingAs($this->superAdmin())
            ->put("/administration/permissions/{$role->id}", [])
            ->assertRedirect(route('permissions.index'));

        $role->refresh();
        $this->assertFalse($role->hasPermissionTo('billing.report'));
        $this->assertCount(0, $role->permissions);
    }

    public function test_permission_assignment_rejects_unknown_permission_ids(): void
    {
        $role = Role::findByName('Cashier');

        $this->actingAs($this->superAdmin())
            ->put("/administration/permissions/{$role->id}", [
                'permissions' => [99999],
            ])
            ->assertSessionHasErrors('permissions.0');
    }

    public function test_registration_staff_home_has_no_administration_navigation(): void
    {
        $this->actingAs($this->userWithRole('Registration Staff'))
            ->get('/home')
            ->assertOk()
            ->assertDontSee('/administration/users');
    }

    public function test_super_admin_home_shows_administration_navigation(): void
    {
        $this->actingAs($this->superAdmin())
            ->get('/home')
            ->assertOk()
            ->assertSee('/administration/users');
    }
}
