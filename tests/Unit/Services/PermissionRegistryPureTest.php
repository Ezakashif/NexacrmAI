<?php

namespace Tests\Unit\Services;

use App\Services\PermissionRegistry;
use Tests\TestCase;

class PermissionRegistryPureTest extends TestCase
{
    private PermissionRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registry = app(PermissionRegistry::class);
    }

    public function test_slug_builds_action_dot_module_format(): void
    {
        $this->assertSame('view.customers', $this->registry->slug('view', 'customers'));
        $this->assertSame('create.tasks', $this->registry->slug('create', 'tasks'));
        $this->assertSame('website_lead.demo', $this->registry->slug('website_lead', 'demo'));
    }

    public function test_legacy_slug_map_contains_expected_mappings(): void
    {
        $map = $this->registry->legacySlugMap();

        $this->assertSame('view.customers', $map['customers.view']);
        $this->assertSame('create.leads', $map['leads.create']);
        $this->assertSame('convert.leads', $map['leads.convert']);
        $this->assertSame('log.leads', $map['leads.activities.create']);
        $this->assertSame('change_status.tasks', $map['tasks.change_status']);
        $this->assertSame('view.users', $map['users.manage']);
        $this->assertSame('view.roles', $map['roles.manage']);
        $this->assertSame('view.activity_logs', $map['activity-logs.view']);
        $this->assertSame('website_lead.demo', $map['demo.website-lead']);
    }

    public function test_all_slugs_includes_every_configured_module_action(): void
    {
        $slugs = $this->registry->allSlugs();

        $this->assertTrue($slugs->contains('view.customers'));
        $this->assertTrue($slugs->contains('import.customers'));
        $this->assertTrue($slugs->contains('view_all.leads'));
        $this->assertTrue($slugs->contains('assign.tasks'));
        $this->assertTrue($slugs->contains('view.reports'));
        $this->assertTrue($slugs->contains('export.reports'));
        $this->assertTrue($slugs->contains('website_lead.demo'));
        $this->assertFalse($slugs->contains('customers.view'));
    }

    public function test_all_slugs_count_matches_config_modules(): void
    {
        $expectedCount = collect(config('permissions.modules', []))
            ->sum(fn (array $module) => count($module['actions']));

        $this->assertSame($expectedCount, $this->registry->allSlugs()->count());
    }

    public function test_modules_returns_configured_permission_modules(): void
    {
        $modules = $this->registry->modules();

        $this->assertArrayHasKey('customers', $modules);
        $this->assertArrayHasKey('leads', $modules);
        $this->assertSame('Customers', $modules['customers']['label']);
        $this->assertArrayHasKey('view', $modules['customers']['actions']);
    }
}
