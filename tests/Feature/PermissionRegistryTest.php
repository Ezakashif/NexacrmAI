<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Services\PermissionRegistry;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionRegistryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    public function test_permissions_use_action_module_slug_format(): void
    {
        $this->assertDatabaseHas('permissions', ['slug' => 'view.customers']);
        $this->assertDatabaseHas('permissions', ['slug' => 'create.tasks']);
        $this->assertDatabaseHas('permissions', ['slug' => 'website_lead.demo']);
        $this->assertDatabaseMissing('permissions', ['slug' => 'customers.view']);
    }

    public function test_sync_creates_permissions_for_new_module(): void
    {
        config()->set('permissions.modules.reports', [
            'label' => 'Reports',
            'actions' => [
                'view' => 'View',
                'export' => 'Export',
            ],
        ]);

        app(PermissionRegistry::class)->sync();

        $this->assertDatabaseHas('permissions', ['slug' => 'view.reports', 'group' => 'reports']);
        $this->assertDatabaseHas('permissions', ['slug' => 'export.reports', 'group' => 'reports']);
    }

    public function test_sync_removes_permissions_not_in_registry(): void
    {
        Permission::create([
            'name' => 'Manual Permission',
            'slug' => 'test.manual',
            'group' => 'test',
        ]);

        app(PermissionRegistry::class)->sync();

        $this->assertDatabaseMissing('permissions', ['slug' => 'test.manual']);
    }
}
