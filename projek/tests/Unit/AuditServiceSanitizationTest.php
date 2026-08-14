<?php

namespace Tests\Unit;

use App\Services\AuditService;
use PHPUnit\Framework\TestCase;

class AuditServiceSanitizationTest extends TestCase
{
    public function test_exact_sensitive_keys_are_removed(): void
    {
        $sanitized = app(AuditService::class)->sanitize([
            'name' => 'Budi',
            'password' => 'secret123',
            'email' => 'budi@example.com',
        ]);

        $this->assertSame(['name' => 'Budi', 'email' => 'budi@example.com'], $sanitized);
        $this->assertArrayNotHasKey('password', $sanitized);
    }

    public function test_whole_adr_009_denylist_is_stripped(): void
    {
        $payload = [
            'password' => 'x',
            'password_confirmation' => 'x',
            'remember_token' => 'x',
            'api_token' => 'x',
            'access_token' => 'x',
            'refresh_token' => 'x',
            'client_secret' => 'x',
            'private_key' => 'x',
            'encryption_key' => 'x',
        ];

        $sanitized = app(AuditService::class)->sanitize($payload);

        $this->assertSame([], $sanitized);
    }

    public function test_nested_arrays_are_sanitized_recursively(): void
    {
        $sanitized = app(AuditService::class)->sanitize([
            'user' => [
                'name' => 'Siti',
                'token' => 'abc123',
            ],
            'meta' => [
                'nested' => [
                    'private_key' => 'secret',
                    'keep' => 'value',
                ],
            ],
        ]);

        $this->assertSame([
            'user' => ['name' => 'Siti'],
            'meta' => ['nested' => ['keep' => 'value']],
        ], $sanitized);
    }

    public function test_sensitive_keys_are_stripped_case_insensitively(): void
    {
        $sanitized = app(AuditService::class)->sanitize([
            'PASSWORD' => 'x',
            'Access_Token' => 'x',
            'name' => 'ok',
        ]);

        $this->assertSame(['name' => 'ok'], $sanitized);
    }

    public function test_non_sensitive_data_is_preserved(): void
    {
        $payload = [
            'identifier_pat' => 'PAT-000001',
            'name' => 'Budi',
            'status' => 'UNPAID',
            'amount' => '100000.00',
        ];

        $this->assertSame($payload, app(AuditService::class)->sanitize($payload));
    }
}
