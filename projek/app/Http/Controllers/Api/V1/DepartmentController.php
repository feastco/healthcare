<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Department\StoreDepartmentRequest;
use App\Http\Requests\Department\UpdateDepartmentRequest;
use App\Http\Resources\DepartmentResource;
use App\Models\Department;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class DepartmentController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Department::class);

        return DepartmentResource::collection(Department::latest('id')->paginate());
    }

    public function store(StoreDepartmentRequest $request): JsonResponse
    {
        Gate::authorize('create', Department::class);

        $department = Department::create($request->validated());

        return response()->json([
            'data' => new DepartmentResource($department),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $department = Department::findOrFail($id);

        Gate::authorize('view', $department);

        return response()->json([
            'data' => new DepartmentResource($department),
        ]);
    }

    public function update(UpdateDepartmentRequest $request, int $id): JsonResponse
    {
        $department = Department::findOrFail($id);

        Gate::authorize('update', $department);

        $department->update($request->validated());

        return response()->json([
            'data' => new DepartmentResource($department->fresh()),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $department = Department::findOrFail($id);

        Gate::authorize('delete', $department);

        if ($department->doctors()->exists()) {
            return response()->json([
                'message' => 'Cannot delete department that still has doctor records.',
                'errors' => ['department' => ['BR: department still has related records.']],
            ], 422);
        }

        try {
            $department->delete();
        } catch (QueryException) {
            return response()->json([
                'message' => 'Cannot delete department that is still referenced by other records.',
                'errors' => ['department' => ['BR: department still has related records.']],
            ], 422);
        }

        return response()->json(['data' => ['message' => 'Department deleted successfully.']]);
    }
}
