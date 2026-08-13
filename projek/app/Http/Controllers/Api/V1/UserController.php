<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', User::class);

        return UserResource::collection(User::with('roles')->paginate());
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        Gate::authorize('create', User::class);

        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        if (! empty($validated['roles'])) {
            $user->syncRoles(Role::whereIn('id', $validated['roles'])->get());
        }

        return response()->json([
            'data' => new UserResource($user->load('roles')),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $user = User::with('roles')->findOrFail($id);

        Gate::authorize('view', $user);

        return response()->json([
            'data' => new UserResource($user),
        ]);
    }

    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        Gate::authorize('update', $user);

        $validated = $request->validated();

        $user->update([
            'name' => $validated['name'] ?? $user->name,
            'email' => $validated['email'] ?? $user->email,
        ]);

        if (array_key_exists('password', $validated)) {
            $user->update(['password' => $validated['password']]);
        }

        if (array_key_exists('roles', $validated)) {
            $user->syncRoles(Role::whereIn('id', $validated['roles'] ?? [])->get());
        }

        return response()->json([
            'data' => new UserResource($user->load('roles')),
        ]);
    }
}
