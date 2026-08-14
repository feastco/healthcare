<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\Patient;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'action' => AuditService::ACTION_CREATE,
            'entity_type' => Patient::class,
            'entity_id' => Patient::factory(),
            'before_state' => [],
            'after_state' => ['identifier_pat' => 'PAT-000001', 'name' => 'Budi'],
            'created_at' => now(),
        ];
    }
}
