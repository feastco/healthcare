<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Doctor\StoreDoctorRequest;
use App\Http\Requests\Doctor\UpdateDoctorRequest;
use App\Http\Resources\DoctorResource;
use App\Models\Doctor;
use App\Services\AuditService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
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

        $doctor = DB::transaction(function () use ($request) {
            $doctor = Doctor::create($request->validated());

            app(AuditService::class)->created($doctor, actor: $request->user());

            return $doctor;
        });

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

        $doctor = DB::transaction(function () use ($request, $doctor) {
            $before = $doctor->getAttributes();

            $doctor->update($request->validated());

            app(AuditService::class)->updated(
                $doctor,
                before: $before,
                after: $doctor->getAttributes(),
                actor: $request->user(),
            );

            return $doctor->fresh(['department']);
        });

        return response()->json([
            'data' => new DoctorResource($doctor),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
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
            DB::transaction(function () use ($doctor, $request) {
                $before = $doctor->getAttributes();

                $doctor->delete();

                app(AuditService::class)->deleted($doctor, before: $before, actor: $request->user());
            });
        } catch (QueryException) {
            return response()->json([
                'message' => 'Cannot delete doctor that is still referenced by other records.',
                'errors' => ['doctor' => ['BR: doctor still has related records.']],
            ], 422);
        }

        return response()->json(['data' => ['message' => 'Doctor deleted successfully.']]);
    }
}
