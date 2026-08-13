<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Schedule\StoreScheduleRequest;
use App\Http\Requests\Schedule\UpdateScheduleRequest;
use App\Http\Resources\DoctorScheduleResource;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class DoctorScheduleController extends Controller
{
    public function index(int $doctorId): JsonResponse
    {
        $doctor = Doctor::findOrFail($doctorId);

        Gate::authorize('viewAny', DoctorSchedule::class);

        return response()->json([
            'data' => DoctorScheduleResource::collection(
                $doctor->schedules()->orderBy('day_of_week')->orderBy('start_time')->get()
            ),
        ]);
    }

    public function store(StoreScheduleRequest $request, int $doctorId): JsonResponse
    {
        $doctor = Doctor::findOrFail($doctorId);

        Gate::authorize('create', DoctorSchedule::class);

        $schedule = DoctorSchedule::create([
            'doctor_id' => $doctor->id,
            'day_of_week' => $request->validated('day_of_week'),
            'start_time' => $request->validated('start_time'),
            'end_time' => $request->validated('end_time'),
        ]);

        return response()->json([
            'data' => new DoctorScheduleResource($schedule),
        ], 201);
    }

    public function update(UpdateScheduleRequest $request, int $id): JsonResponse
    {
        $schedule = DoctorSchedule::findOrFail($id);

        Gate::authorize('update', $schedule);

        $schedule->update($request->validated());

        return response()->json([
            'data' => new DoctorScheduleResource($schedule->fresh()),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $schedule = DoctorSchedule::findOrFail($id);

        Gate::authorize('delete', $schedule);

        try {
            $schedule->delete();
        } catch (QueryException) {
            return response()->json([
                'message' => 'Cannot delete schedule that is still referenced by other records.',
                'errors' => ['schedule' => ['BR: schedule still has related records.']],
            ], 422);
        }

        return response()->json(['data' => ['message' => 'Schedule deleted successfully.']]);
    }
}
