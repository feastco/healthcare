<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\GeneratePatientIdentifierAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patient\StorePatientRequest;
use App\Http\Requests\Patient\UpdatePatientRequest;
use App\Http\Resources\PatientResource;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
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

        $patient = Patient::create([
            'identifier_pat' => $generateIdentifier->handle(),
            'name' => $request->validated('name'),
            'dob' => $request->validated('dob'),
            'gender' => $request->validated('gender'),
        ]);

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

        $patient->update($request->validated());

        return response()->json([
            'data' => new PatientResource($patient->fresh()),
        ]);
    }
}
