<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Schedule\WebScheduleStoreRequest;
use App\Http\Requests\Schedule\WebScheduleUpdateRequest;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Services\AuditService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', DoctorSchedule::class);

        return view('master-data.schedules.index', [
            'schedules' => DoctorSchedule::with('doctor')
                ->orderBy('day_of_week')
                ->orderBy('start_time')
                ->paginate(10),
            'days' => $this->dayOptions(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', DoctorSchedule::class);

        return view('master-data.schedules.create', $this->formOptions());
    }

    public function store(WebScheduleStoreRequest $request): RedirectResponse
    {
        Gate::authorize('create', DoctorSchedule::class);

        $schedule = DB::transaction(function () use ($request) {
            $schedule = DoctorSchedule::create([
                ...$request->validated(),
                'doctor_id' => (int) $request->validated('doctor_id'),
            ]);

            app(AuditService::class)->created($schedule, actor: $request->user());

            return $schedule;
        });

        return redirect()->route('schedules.index')
            ->with('success', 'Schedule created successfully.');
    }

    public function edit(DoctorSchedule $schedule): View
    {
        Gate::authorize('update', $schedule);

        return view('master-data.schedules.edit', [
            'schedule' => $schedule,
            ...$this->formOptions(),
        ]);
    }

    public function update(WebScheduleUpdateRequest $request, DoctorSchedule $schedule): RedirectResponse
    {
        Gate::authorize('update', $schedule);

        DB::transaction(function () use ($request, $schedule) {
            $before = $schedule->getAttributes();

            $schedule->update([
                ...$request->validated(),
                'doctor_id' => (int) $request->validated('doctor_id'),
            ]);

            app(AuditService::class)->updated(
                $schedule,
                before: $before,
                after: $schedule->fresh()->getAttributes(),
                actor: $request->user(),
            );
        });

        return redirect()->route('schedules.index')
            ->with('success', 'Schedule updated successfully.');
    }

    public function destroy(Request $request, DoctorSchedule $schedule): RedirectResponse
    {
        Gate::authorize('delete', $schedule);

        try {
            DB::transaction(function () use ($request, $schedule) {
                $before = $schedule->getAttributes();

                $schedule->delete();

                app(AuditService::class)->deleted($schedule, before: $before, actor: $request->user());
            });
        } catch (QueryException) {
            return redirect()->back()
                ->with('error', 'Cannot delete schedule that is still referenced by other records.');
        }

        return redirect()->route('schedules.index')
            ->with('success', 'Schedule deleted successfully.');
    }

    private function formOptions(): array
    {
        return [
            'doctors' => Doctor::orderBy('name')->get(['id', 'name']),
            'days' => $this->dayOptions(),
        ];
    }

    private function dayOptions(): array
    {
        return [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ];
    }
}
