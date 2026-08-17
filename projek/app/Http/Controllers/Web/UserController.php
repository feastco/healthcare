<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', User::class);

        return view('administration.users.index', [
            'users' => User::with('roles')->latest('id')->paginate(10),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', User::class);

        return view('administration.users.create', [
            'roles' => Role::orderBy('name')->get(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        Gate::authorize('create', User::class);

        $user = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
        ]);

        $user->syncRoles($request->validated('roles', []));

        return redirect()->route('users.index')
            ->with('success', 'User created successfully.');
    }

    public function edit(User $user): View
    {
        Gate::authorize('update', $user);

        return view('administration.users.edit', [
            'user' => $user->load('roles'),
            'roles' => Role::orderBy('name')->get(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        Gate::authorize('update', $user);

        $user->update([
            'name' => $request->validated('name', $user->name),
            'email' => $request->validated('email', $user->email),
        ]);

        if ($request->filled('password')) {
            $user->update(['password' => $request->validated('password')]);
        }

        $user->syncRoles($request->validated('roles', []));

        return redirect()->route('users.index')
            ->with('success', 'User updated successfully.');
    }
}
