<?php

namespace Tests\Unit;

use App\Support\Navigation;
use PHPUnit\Framework\TestCase;

class NavigationTest extends TestCase
{
    public function test_super_admin_sees_main_menus_without_audit_logs(): void
    {
        $groups = Navigation::forRoles(['Super Admin']);

        $this->assertSame(
            ['Dashboard', 'Administration', 'Master Data', 'Operations'],
            array_column($groups, 'title'),
        );

        $allItems = array_merge(...array_column($groups, 'items'));
        $this->assertNotContains('Audit Logs', array_column($allItems, 'name'));
        $this->assertNotContains('/monitoring/audit-logs', array_column($allItems, 'path'));
    }

    public function test_registration_staff_sees_master_data_and_operations(): void
    {
        $groups = Navigation::forRoles(['Registration Staff']);

        $this->assertSame(
            ['Master Data', 'Operations'],
            array_column($groups, 'title'),
        );

        $operations = $groups[1]['items'];
        $this->assertSame(['Appointments'], array_column($operations, 'name'));
    }

    public function test_doctor_sees_only_my_queue_in_operations(): void
    {
        $groups = Navigation::forRoles(['Doctor']);

        $this->assertCount(1, $groups);
        $this->assertSame('Operations', $groups[0]['title']);
        $this->assertSame(['My Queue'], array_column($groups[0]['items'], 'name'));
    }

    public function test_cashier_sees_only_invoices_in_operations(): void
    {
        $groups = Navigation::forRoles(['Cashier']);

        $this->assertCount(1, $groups);
        $this->assertSame('Operations', $groups[0]['title']);
        $this->assertSame(['Invoices'], array_column($groups[0]['items'], 'name'));
    }

    public function test_it_admin_sees_dashboard_and_monitoring(): void
    {
        $groups = Navigation::forRoles(['IT/Admin']);

        $this->assertSame(
            ['Dashboard', 'Monitoring'],
            array_column($groups, 'title'),
        );

        $monitoring = $groups[1]['items'];
        $this->assertSame(['Audit Logs'], array_column($monitoring, 'name'));
        $this->assertSame(['/monitoring/audit-logs'], array_column($monitoring, 'path'));
    }

    public function test_empty_roles_returns_no_groups(): void
    {
        $this->assertSame([], Navigation::forRoles([]));
    }

    public function test_unknown_role_returns_no_groups(): void
    {
        $this->assertSame([], Navigation::forRoles(['Guest']));
    }

    public function test_every_menu_item_has_a_supported_icon(): void
    {
        $icons = Navigation::icons();

        foreach (Navigation::groups() as $group) {
            foreach ($group['items'] as $item) {
                $this->assertArrayHasKey($item['icon'], $icons);
                $this->assertNotSame('', Navigation::iconSvg($item['icon']));
            }
        }
    }

    public function test_unknown_icon_returns_empty_string(): void
    {
        $this->assertSame('', Navigation::iconSvg('does-not-exist'));
    }
}
