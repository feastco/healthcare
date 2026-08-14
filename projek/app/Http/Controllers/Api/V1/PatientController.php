<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\GeneratePatientIdentifierAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patient\StorePatientRequest;
use App\Http\Requests\Patient\UpdatePatientRequest;
use App\Http\Resources\PatientResource;
use App\Models\Patient;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class PatientController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Patient::class);

        return PatientResource::collection(Patient::latest('id')->paginate());
    }

    public function store(StorePatientRequest $request, GeneratePatientIdentifierAction $generateIdentifier): JsonResponse
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

        return response()->json([
            'data' => new PatientResource($patient),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $patient = Patient::findOrFail($id);

        Gate::authorize('view', $patient);

        return response()->json([
            'data' => new PatientResource($patient),
        ]);
    }

    public function update(UpdatePatientRequest $request, int $id): JsonResponse
    {
        $patient = Patient::findOrFail($id);

        Gate::authorize('update', $patient);

        $patient = DB::transaction(function () use ($request, $patient) {
            $before = $patient->getAttributes();

            $patient->update($request->validated());

            app(AuditService::class)->updated(
                $patient,
                before: $before,
                after: $patient->getAttributes(),
                actor: $request->user(),
            );

            return $patient->fresh();
        });

        return response()->json([
            'data' => new PatientResource($patient),
        ]);
    }
}
