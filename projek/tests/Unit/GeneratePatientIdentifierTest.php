<?php

namespace Tests\Unit;

use App\Actions\GeneratePatientIdentifierAction;
use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeneratePatientIdentifierTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_identifier_with_year_and_six_digit_sequence(): void
    {
        $identifier = app(GeneratePatientIdentifierAction::class)->handle();

        $this->assertMatchesRegularExpression('/^PAT-\d{4}-\d{6}$/', $identifier);
        $this->assertSame('PAT-'.now()->format('Y').'-000001', $identifier);
    }

    public function test_it_increments_sequence_from_latest_identifier(): void
    {
        Patient::factory()->create([
            'identifier_pat' => 'PAT-'.now()->format('Y').'-000042',
        ]);

        $identifier = app(GeneratePatientIdentifierAction::class)->handle();

        $this->assertSame('PAT-'.now()->format('Y').'-000043', $identifier);
    }
}
