<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Role\GrantPermissionsRequest;
use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Role::class);

        return RoleResource::collection(Role::with('permissions')->paginate());
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        Gate::authorize('create', Role::class);

        $role = Role::create(['name' => $request->validated('name')]);

        if (! empty($request->validated('permissions'))) {
            $role->syncPermissions(Permission::whereIn('id', $request->validated('permissions'))->get());
        }

        return response()->json([
            'data' => new RoleResource($role->load('permissions')),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $role = Role::with('permissions')->findOrFail($id);

        Gate::authorize('view', $role);

        return response()->json([
            'data' => new RoleResource($role),
        ]);
    }

    public function update(UpdateRoleRequest $request, int $id): JsonResponse
    {
        $role = Role::findOrFail($id);

        Gate::authorize('update', $role);

        if ($request->has('name')) {
            $role->name = $request->validated('name');
        }

        if ($request->has('permissions')) {
            $role->syncPermissions(Permission::whereIn('id', $request->validated('permissions'))->get());
        }

        $role->save();

        return response()->json([
            'data' => new RoleResource($role->fresh(['permissions'])),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $role = Role::findOrFail($id);

        Gate::authorize('delete', $role);

        if ($role->users()->exists()) {
            return response()->json([
                'message' => 'Cannot delete role that is still assigned to users.',
                'errors' => ['role' => ['BR-04: role still has user assignments.']],
            ], 422);
        }

        try {
            $role->delete();
        } catch (QueryException) {
            return response()->json([
                'message' => 'Cannot delete role that is still assigned to users.',
                'errors' => ['role' => ['BR-04: role still has user assignments.']],
            ], 422);
        }

        return response()->json(['data' => ['message' => 'Role deleted successfully.']]);
    }

    public function grantPermissions(GrantPermissionsRequest $request, int $roleId): JsonResponse
    {
        $role = Role::findOrFail($roleId);

        Gate::authorize('grantPermissions', $role);

        $role->givePermissionTo(Permission::whereIn('id', $request->validated('permissions'))->get());

        return response()->json([
            'data' => new RoleResource($role->load('permissions')),
        ]);
    }

    public function revokePermission(Request $request, int $roleId, int $permissionId): JsonResponse
    {
        $role = Role::findOrFail($roleId);

        Gate::authorize('revokePermission', $role);

        $permission = Permission::findOrFail($permissionId);

        $role->revokePermissionTo($permission);

        return response()->json([
            'data' => new RoleResource($role->fresh(['permissions'])),
        ]);
    }
}
