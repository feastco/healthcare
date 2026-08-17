<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Department\StoreDepartmentRequest;
use App\Http\Requests\Department\UpdateDepartmentRequest;
use App\Models\Department;
use App\Services\AuditService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Department::class);

        return view('master-data.departments.index', [
            'departments' => Department::latest('id')->paginate(10),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Department::class);

        return view('master-data.departments.create');
    }

    public function store(StoreDepartmentRequest $request): RedirectResponse
    {
        Gate::authorize('create', Department::class);

        $department = DB::transaction(function () use ($request) {
            $department = Department::create($request->validated());

            app(AuditService::class)->created($department, actor: $request->user());

            return $department;
        });

        return redirect()->route('departments.show', $department)
            ->with('success', 'Department created successfully.');
    }

    public function show(Department $department): View
    {
        Gate::authorize('view', $department);

        return view('master-data.departments.show', [
            'department' => $department,
        ]);
    }

    public function edit(Department $department): View
    {
        Gate::authorize('update', $department);

        return view('master-data.departments.edit', [
            'department' => $department,
        ]);
    }

    public function update(UpdateDepartmentRequest $request, Department $department): RedirectResponse
    {
        Gate::authorize('update', $department);

        DB::transaction(function () use ($request, $department) {
            $before = $department->getAttributes();

            $department->update($request->validated());

            app(AuditService::class)->updated(
                $department,
                before: $before,
                after: $department->fresh()->getAttributes(),
                actor: $request->user(),
            );
        });

        return redirect()->route('departments.show', $department)
            ->with('success', 'Department updated successfully.');
    }

    public function destroy(Request $request, Department $department): RedirectResponse
    {
        Gate::authorize('delete', $department);

        if ($department->doctors()->exists()) {
            return redirect()->back()
                ->with('error', 'Cannot delete department that still has doctor records.');
        }

        try {
            DB::transaction(function () use ($request, $department) {
                $before = $department->getAttributes();

                $department->delete();

                app(AuditService::class)->deleted($department, before: $before, actor: $request->user());
            });
        } catch (QueryException) {
            return redirect()->back()
                ->with('error', 'Cannot delete department that is still referenced by other records.');
        }

        return redirect()->route('departments.index')
            ->with('success', 'Department deleted successfully.');
    }
}
