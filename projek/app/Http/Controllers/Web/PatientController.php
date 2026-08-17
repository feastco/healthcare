<?php

namespace App\Http\Controllers\Web;

use App\Actions\GeneratePatientIdentifierAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patient\StorePatientRequest;
use App\Http\Requests\Patient\UpdatePatientRequest;
use App\Models\Patient;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PatientController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Patient::class);

        return view('master-data.patients.index', [
            'patients' => Patient::latest('id')->paginate(10),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Patient::class);

        return view('master-data.patients.create');
    }

    public function store(StorePatientRequest $request, GeneratePatientIdentifierAction $generateIdentifier): RedirectResponse
    {
        Gate::authorize('create', Patient::class);

        $patient = DB::transaction(function () use ($request, $generateIdentifier) {
            $patient = Patient::create([
                'identifier_pat' => $generateIdentifier->handle(),
                'name' => $request->validated('name'),
                'dob' => $request->validated('dob'),
                'gender' => $request->validated('gender'),
            ]);

            app(AuditService::class)->created($patient, actor: $request->user());

            return $patient;
        });

        return redirect()->route('patients.show', $patient)
            ->with('success', 'Patient created successfully.');
    }

    public function show(Patient $patient): View
    {
        Gate::authorize('view', $patient);

        return view('master-data.patients.show', [
            'patient' => $patient,
        ]);
    }

    public function edit(Patient $patient): View
    {
        Gate::authorize('update', $patient);

        return view('master-data.patients.edit', [
            'patient' => $patient,
        ]);
    }

    public function update(UpdatePatientRequest $request, Patient $patient): RedirectResponse
    {
        Gate::authorize('update', $patient);

        DB::transaction(function () use ($request, $patient) {
            $before = $patient->getAttributes();

            $patient->update($request->validated());

            app(AuditService::class)->updated(
                $patient,
                before: $before,
                after: $patient->fresh()->getAttributes(),
                actor: $request->user(),
            );
        });

        return redirect()->route('patients.show', $patient)
            ->with('success', 'Patient updated successfully.');
    }
}
