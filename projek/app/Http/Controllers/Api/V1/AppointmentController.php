<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CreateAppointmentAction;
use App\Exceptions\AppointmentConflictException;
use App\Exceptions\AppointmentUnavailableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Appointment\StoreAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

class AppointmentController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Appointment::class);

        return AppointmentResource::collection(
            Appointment::with(['patient', 'doctor'])->latest('id')->paginate()
        );
    }

    public function store(StoreAppointmentRequest $request, CreateAppointmentAction $createAppointment): JsonResponse
    {
        Gate::authorize('create', Appointment::class);

        try {
            $appointment = $createAppointment->handle(
                patientId: $request->validated('patient_id'),
                doctorId: $request->validated('doctor_id'),
                startsAt: Carbon::parse($request->validated('starts_at')),
                endsAt: Carbon::parse($request->validated('ends_at')),
            );
        } catch (AppointmentUnavailableException) {
            return response()->json([
                'message' => 'Doctor is not scheduled to work during the requested time.',
                'errors' => ['starts_at' => ['Doctor has no schedule covering the requested time.']],
            ], 422);
        } catch (AppointmentConflictException) {
            return response()->json([
                'message' => 'Doctor already has an overlapping appointment during the requested time.',
                'errors' => ['starts_at' => ['Doctor already has an overlapping appointment.']],
            ], 422);
        } catch (QueryException $e) {
            if ($this->isExclusionViolation($e)) {
                return response()->json([
                    'message' => 'Doctor already has an overlapping appointment during the requested time.',
                    'errors' => ['starts_at' => ['Doctor already has an overlapping appointment.']],
                ], 422);
            }

            throw $e;
        }

        return response()->json([
            'data' => new AppointmentResource($appointment->load(['patient', 'doctor'])),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $appointment = Appointment::with(['patient', 'doctor'])->findOrFail($id);

        Gate::authorize('view', $appointment);

        return response()->json([
            'data' => new AppointmentResource($appointment),
        ]);
    }

    private function isExclusionViolation(QueryException $e): bool
    {
        return $e->getCode() === '23P01'
            || str_contains($e->getMessage(), '23P01');
    }
}
