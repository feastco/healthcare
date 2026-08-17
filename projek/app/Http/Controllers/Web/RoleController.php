<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Role::class);

        return view('administration.roles.index', [
            'roles' => Role::withCount(['users', 'permissions'])->orderBy('name')->paginate(10),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Role::class);

        return view('administration.roles.create', [
            'permissions' => Permission::orderBy('name')->get(),
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        Gate::authorize('create', Role::class);

        $role = Role::create(['name' => $request->validated('name')]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->validated('permissions'));
        }

        return redirect()->route('roles.index')
            ->with('success', 'Role created successfully.');
    }

    public function edit(Role $role): View
    {
        Gate::authorize('update', $role);

        return view('administration.roles.edit', [
            'role' => $role->load('permissions'),
            'permissions' => Permission::orderBy('name')->get(),
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        Gate::authorize('update', $role);

        $role->update(['name' => $request->validated('name', $role->name)]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->validated('permissions'));
        }

        return redirect()->route('roles.index')
            ->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        Gate::authorize('delete', $role);

        if ($role->users()->exists()) {
            return redirect()->back()
                ->with('error', 'Role cannot be deleted because it has user assignments.');
        }

        try {
            $role->delete();
        } catch (QueryException) {
            return redirect()->back()
                ->with('error', 'Role cannot be deleted because it is still in use.');
        }

        return redirect()->route('roles.index')
            ->with('success', 'Role deleted successfully.');
    }
}
