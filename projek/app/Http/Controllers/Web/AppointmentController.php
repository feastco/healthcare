<?php

namespace App\Http\Controllers\Web;

use App\Actions\CreateAppointmentAction;
use App\Exceptions\AppointmentConflictException;
use App\Exceptions\AppointmentUnavailableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Appointment\StoreAppointmentRequest;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Services\AuditService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Appointment::class);

        return view('operations.appointments.index', [
            'appointments' => Appointment::with(['patient', 'doctor'])
                ->latest('id')
                ->paginate(10),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Appointment::class);

        return view('operations.appointments.create', $this->formOptions());
    }

    public function store(StoreAppointmentRequest $request, CreateAppointmentAction $createAppointment): RedirectResponse
    {
        Gate::authorize('create', Appointment::class);

        $startsAt = Carbon::parse($request->validated('starts_at'));
        $endsAt = Carbon::parse($request->validated('ends_at'));

        try {
            $appointment = DB::transaction(function () use ($request, $createAppointment, $startsAt, $endsAt) {
                $appointment = $createAppointment->handle(
                    (int) $request->validated('patient_id'),
                    (int) $request->validated('doctor_id'),
                    $startsAt,
                    $endsAt,
                );

                app(AuditService::class)->created($appointment, actor: $request->user());

                return $appointment;
            });
        } catch (AppointmentUnavailableException) {
            return back()
                ->withInput()
                ->withErrors(['starts_at' => 'Doctor has no schedule covering the requested time.'])
                ->with('error', 'Doctor is not scheduled to work during the requested time.');
        } catch (AppointmentConflictException) {
            return back()
                ->withInput()
                ->withErrors(['starts_at' => 'Doctor already has an overlapping appointment.'])
                ->with('error', 'Doctor already has an overlapping appointment during the requested time.');
        } catch (QueryException $exception) {
            if ($exception->getCode() === '23P01') {
                return back()
                    ->withInput()
                    ->withErrors(['starts_at' => 'Doctor already has an overlapping appointment.'])
                    ->with('error', 'Doctor already has an overlapping appointment during the requested time.');
            }

            throw $exception;
        }

        return redirect()->route('appointments.show', $appointment)
            ->with('success', 'Appointment created successfully.');
    }

    public function show(Appointment $appointment): View
    {
        Gate::authorize('view', $appointment);

        return view('operations.appointments.show', [
            'appointment' => $appointment->load(['patient', 'doctor']),
        ]);
    }

    private function formOptions(): array
    {
        $dayLabels = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ];

        $doctors = Doctor::with('schedules')->orderBy('name')->get();

        $doctorSchedules = $doctors->mapWithKeys(function (Doctor $doctor) use ($dayLabels) {
            return [
                $doctor->id => $doctor->schedules
                    ->sortBy('day_of_week')
                    ->map(fn ($schedule) => [
                        'day' => $dayLabels[$schedule->day_of_week] ?? (string) $schedule->day_of_week,
                        'start_time' => Str::substr($schedule->start_time, 0, 5),
                        'end_time' => Str::substr($schedule->end_time, 0, 5),
                    ])
                    ->values()
                    ->all(),
            ];
        });

        return [
            'patients' => Patient::orderBy('name')->get(['id', 'name']),
            'doctors' => $doctors,
            'doctorSchedules' => $doctorSchedules,
        ];
    }
}
