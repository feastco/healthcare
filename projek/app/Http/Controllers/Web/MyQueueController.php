<?php

namespace App\Http\Controllers\Web;

use App\Actions\TransitionQueueAction;
use App\Enums\AppointmentStatus;
use App\Exceptions\AppointmentStatusTransitionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Appointment\UpdateAppointmentStatusRequest;
use App\Models\Appointment;
use App\Models\Doctor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MyQueueController extends Controller
{
    public function index(): View
    {
        $query = Appointment::query()
            ->with(['patient', 'doctor'])
            ->whereDate('starts_at', now()->toDateString())
            ->whereIn('status', [
                AppointmentStatus::WAITING->value,
                AppointmentStatus::IN_PROGRESS->value,
            ])
            ->orderBy('starts_at');

        if (! auth()->user()->hasRole('Super Admin')) {
            $doctor = $this->currentDoctor();

            if ($doctor === null) {
                $query->whereRaw('1 = 0');
            } else {
                $query->where('doctor_id', $doctor->id);
            }
        }

        return view('operations.my-queue.index', [
            'appointments' => $query->paginate(10),
        ]);
    }

    public function updateStatus(
        UpdateAppointmentStatusRequest $request,
        Appointment $appointment,
        TransitionQueueAction $transitionQueue,
    ): RedirectResponse {
        $target = AppointmentStatus::from($request->validated('status'));

        Gate::authorize('updateStatus', [$appointment, $target]);

        try {
            $transitionQueue->handle($appointment, $target);
        } catch (AppointmentStatusTransitionException) {
            return back()->with('error', 'The requested appointment status transition is not permitted.');
        }

        return back()->with('success', 'Appointment status updated successfully.');
    }

    private function currentDoctor(): ?Doctor
    {
        $user = auth()->user();

        if ($user->hasRole('Super Admin')) {
            return null;
        }

        return Doctor::query()
            ->where('user_id', $user->id)
            ->first();
    }
}
