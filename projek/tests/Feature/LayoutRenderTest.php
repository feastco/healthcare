<?php

namespace Tests\Feature;

use Tests\TestCase;

class LayoutRenderTest extends TestCase
{
    /**
     * The application shell layout renders without an authenticated user,
     * and no role-protected navigation links are exposed.
     */
    public function test_app_layout_renders_unauthenticated_without_protected_navigation(): void
    {
        $this->withoutVite();

        $html = view('layouts.app', ['title' => 'Test'])->render();

        $this->assertStringContainsString('PKU Healthcare Operations Management', $html);
        $this->assertStringContainsString('id="sidebar"', $html);
        $this->assertStringContainsString('Toggle Sidebar', $html);
        $this->assertStringContainsString('Notification', $html);
        $this->assertStringContainsString('Account', $html);
        $this->assertStringContainsString('No notifications yet.', $html);

        $protectedPaths = [
            '/dashboard',
            '/administration/users',
            '/master-data/patients',
            '/operations/appointments',
            '/operations/my-queue',
            '/operations/invoices',
            '/monitoring/audit-logs',
        ];

        foreach ($protectedPaths as $path) {
            $this->assertStringNotContainsString('href="'.$path.'"', $html);
        }
    }
}
