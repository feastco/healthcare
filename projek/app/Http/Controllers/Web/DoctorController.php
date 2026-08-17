<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Doctor\StoreDoctorRequest;
use App\Http\Requests\Doctor\UpdateDoctorRequest;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DoctorController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Doctor::class);

        return view('master-data.doctors.index', [
            'doctors' => Doctor::with('department')->latest('id')->paginate(10),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Doctor::class);

        return view('master-data.doctors.create', $this->formOptions());
    }

    public function store(StoreDoctorRequest $request): RedirectResponse
    {
        Gate::authorize('create', Doctor::class);

        $doctor = DB::transaction(function () use ($request) {
            $doctor = Doctor::create($request->validated());

            app(AuditService::class)->created($doctor, actor: $request->user());

            return $doctor;
        });

        return redirect()->route('doctors.show', $doctor)
            ->with('success', 'Doctor created successfully.');
    }

    public function show(Doctor $doctor): View
    {
        Gate::authorize('view', $doctor);

        return view('master-data.doctors.show', [
            'doctor' => $doctor->load(['department', 'user']),
        ]);
    }

    public function edit(Doctor $doctor): View
    {
        Gate::authorize('update', $doctor);

        return view('master-data.doctors.edit', [
            'doctor' => $doctor,
            ...$this->formOptions(),
        ]);
    }

    public function update(UpdateDoctorRequest $request, Doctor $doctor): RedirectResponse
    {
        Gate::authorize('update', $doctor);

        DB::transaction(function () use ($request, $doctor) {
            $before = $doctor->getAttributes();

            $doctor->update($request->validated());

            app(AuditService::class)->updated(
                $doctor,
                before: $before,
                after: $doctor->fresh()->getAttributes(),
                actor: $request->user(),
            );
        });

        return redirect()->route('doctors.show', $doctor)
            ->with('success', 'Doctor updated successfully.');
    }

    public function destroy(Request $request, Doctor $doctor): RedirectResponse
    {
        Gate::authorize('delete', $doctor);

        if ($doctor->schedules()->exists()) {
            return redirect()->back()
                ->with('error', 'Cannot delete doctor that still has schedule records.');
        }

        try {
            DB::transaction(function () use ($request, $doctor) {
                $before = $doctor->getAttributes();

                $doctor->delete();

                app(AuditService::class)->deleted($doctor, before: $before, actor: $request->user());
            });
        } catch (QueryException) {
            return redirect()->back()
                ->with('error', 'Cannot delete doctor that is still referenced by other records.');
        }

        return redirect()->route('doctors.index')
            ->with('success', 'Doctor deleted successfully.');
    }

    private function formOptions(): array
    {
        return [
            'departments' => Department::orderBy('name')->get(['id', 'name']),
            'users' => User::orderBy('name')->get(['id', 'name']),
        ];
    }
}
