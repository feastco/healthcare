<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Permission::class);

        return view('administration.permissions.index', [
            'roles' => Role::withCount(['users', 'permissions'])->orderBy('name')->get(),
        ]);
    }

    public function edit(Role $role): View
    {
        Gate::authorize('grantPermissions', $role);
        Gate::authorize('revokePermission', $role);

        return view('administration.permissions.edit', [
            'role' => $role->load('permissions'),
            'permissions' => Permission::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        Gate::authorize('grantPermissions', $role);
        Gate::authorize('revokePermission', $role);

        $validated = $request->validate([
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $desired = Permission::whereIn('id', $validated['permissions'] ?? [])->pluck('id')->all();
        $current = $role->permissions->pluck('id')->all();

        $toGrant = array_diff($desired, $current);
        $toRevoke = array_diff($current, $desired);

        if ($toGrant !== []) {
            $role->givePermissionTo(Permission::whereIn('id', $toGrant)->get());
        }

        if ($toRevoke !== []) {
            $role->revokePermissionTo(Permission::whereIn('id', $toRevoke)->get());
        }

        return redirect()->route('permissions.index')
            ->with('success', "Permissions updated for role '{$role->name}'.");
    }
}
