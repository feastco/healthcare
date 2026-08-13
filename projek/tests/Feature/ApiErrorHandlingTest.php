<?php

namespace Tests\Feature;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ApiErrorHandlingTest extends TestCase
{
    public function test_unknown_api_route_returns_json_not_found_envelope(): void
    {
        $response = $this->getJson('/api/v1/does-not-exist');

        $response->assertStatus(404);
        $response->assertJsonStructure(['message']);
    }

    public function test_validation_exception_returns_standard_envelope(): void
    {
        Route::post('/api/v1/test-validation', function (TestValidationRequest $request) {
            return response()->json(['data' => ['ok' => true]]);
        });

        $response = $this->postJson('/api/v1/test-validation', ['name' => '']);

        $response->assertStatus(422);
        $response->assertExactJson([
            'message' => 'Validation failed.',
            'errors' => [
                'name' => ['The name field is required.'],
            ],
        ]);
    }
}

class TestValidationRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required'],
        ];
    }
}
