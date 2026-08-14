<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    public const ACTION_CREATE = 'CREATE';

    public const ACTION_UPDATE = 'UPDATE';

    public const ACTION_DELETE = 'DELETE';

    /**
     * Sensitive credential keys that must never enter an audit payload,
     * in plaintext, hash, or any other representation (ADR-009).
     */
    private const SENSITIVE_KEY_PATTERN =
        '/password|token|secret|credential|_key/i';

    public function created(Model $entity, ?array $after = null, ?User $actor = null): AuditLog
    {
        return $this->record(
            self::ACTION_CREATE,
            $entity,
            before: [],
            after: $after ?? $entity->getAttributes(),
            actor: $actor,
        );
    }

    public function updated(Model $entity, array $before, array $after, ?User $actor = null): AuditLog
    {
        return $this->record(
            self::ACTION_UPDATE,
            $entity,
            before: $before,
            after: $after,
            actor: $actor,
        );
    }

    public function deleted(Model $entity, array $before, ?User $actor = null): AuditLog
    {
        return $this->record(
            self::ACTION_DELETE,
            $entity,
            before: $before,
            after: [],
            actor: $actor,
        );
    }

    public function sanitize(array $payload): array
    {
        $clean = [];

        foreach ($payload as $key => $value) {
            if (preg_match(self::SENSITIVE_KEY_PATTERN, (string) $key) === 1) {
                continue;
            }

            if (is_array($value)) {
                $clean[$key] = $this->sanitize($value);

                continue;
            }

            $clean[$key] = $value;
        }

        return $clean;
    }

    private function record(
        string $action,
        Model $entity,
        array $before,
        array $after,
        ?User $actor,
    ): AuditLog {
        return AuditLog::create([
            'user_id' => ($actor ?? $this->resolveActor())?->id,
            'action' => $action,
            'entity_type' => $entity->getMorphClass(),
            'entity_id' => $entity->getKey(),
            'before_state' => $this->sanitize($before),
            'after_state' => $this->sanitize($after),
        ]);
    }

    private function resolveActor(): ?User
    {
        return Auth::guard('sanctum')->user();
    }
}
