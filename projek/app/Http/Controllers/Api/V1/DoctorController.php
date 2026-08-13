<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Doctor\StoreDoctorRequest;
use App\Http\Requests\Doctor\UpdateDoctorRequest;
use App\Http\Resources\DoctorResource;
use App\Models\Doctor;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class DoctorController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Doctor::class);

        return DoctorResource::collection(Doctor::with('department')->latest('id')->paginate());
    }

    public function store(StoreDoctorRequest $request): JsonResponse
    {
        Gate::authorize('create', Doctor::class);

        $doctor = Doctor::create($request->validated());

        return response()->json([
            'data' => new DoctorResource($doctor->load('department')),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $doctor = Doctor::with('department')->findOrFail($id);

        Gate::authorize('view', $doctor);

        return response()->json([
            'data' => new DoctorResource($doctor),
        ]);
    }

    public function update(UpdateDoctorRequest $request, int $id): JsonResponse
    {
        $doctor = Doctor::findOrFail($id);

        Gate::authorize('update', $doctor);

        $doctor->update($request->validated());

        return response()->json([
            'data' => new DoctorResource($doctor->fresh(['department'])),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $doctor = Doctor::findOrFail($id);

        Gate::authorize('delete', $doctor);

        if ($doctor->schedules()->exists()) {
            return response()->json([
                'message' => 'Cannot delete doctor that still has schedule records.',
                'errors' => ['doctor' => ['BR: doctor still has related records.']],
            ], 422);
        }

        try {
            $doctor->delete();
        } catch (QueryException) {
            return response()->json([
                'message' => 'Cannot delete doctor that is still referenced by other records.',
                'errors' => ['doctor' => ['BR: doctor still has related records.']],
            ], 422);
        }

        return response()->json(['data' => ['message' => 'Doctor deleted successfully.']]);
    }
}
