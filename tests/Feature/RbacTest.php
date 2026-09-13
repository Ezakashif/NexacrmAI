<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    public function test_admin_has_all_permissions(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertTrue($admin->hasRole('admin'));
        $this->assertTrue($admin->hasPermission('view.users'));
        $this->assertTrue($admin->hasPermission('view.roles'));
        $this->assertTrue($admin->hasPermission('create.tasks'));
        $this->assertTrue($admin->canAssignTasks());
        $this->assertTrue($admin->canViewAllLeads());
        $this->assertTrue($admin->canAssignLeads());
    }

    public function test_sales_rep_has_limited_task_and_lead_permissions(): void
    {
        $salesRep = User::factory()->create();

        $this->assertTrue($salesRep->hasRole('sales'));
        $this->assertTrue($salesRep->hasPermission('view.leads'));
        $this->assertTrue($salesRep->hasPermission('view.tasks'));
        $this->assertTrue($salesRep->hasPermission('change_status.tasks'));
        $this->assertTrue($salesRep->hasPermission('update.tasks'));
        $this->assertTrue($salesRep->hasPermission('delete.tasks'));
        $this->assertTrue($salesRep->hasPermission('view_own.activity_logs'));
        $this->assertFalse($salesRep->hasPermission('view_all.tasks'));
        $this->assertFalse($salesRep->hasPermission('view_all.leads'));
        $this->assertFalse($salesRep->hasPermission('assign.leads'));
        $this->assertFalse($salesRep->hasPermission('create.tasks'));
        $this->assertFalse($salesRep->hasPermission('view.users'));
        $this->assertFalse($salesRep->canAssignTasks());
        $this->assertFalse($salesRep->canAssignLeads());
    }

    public function test_manager_system_role_is_removed(): void
    {
        $this->assertFalse(Role::query()->where('slug', 'manager')->exists());
        $this->assertTrue(Role::query()->where('slug', 'admin')->where('is_system', true)->exists());
        $this->assertTrue(Role::query()->where('slug', 'sales')->exists());
        $this->assertFalse(Role::query()->where('slug', 'sales')->where('is_system', true)->exists());
        $this->assertSame(1, Role::query()->where('is_system', true)->count());
    }

    public function test_user_can_have_multiple_custom_roles(): void
    {
        $user = User::factory()->create();
        $customRole = Role::query()->create([
            'name' => 'Team Lead',
            'slug' => 'team-lead',
            'description' => 'Custom role',
            'is_system' => false,
        ]);

        $assignPermission = Permission::query()->where('slug', 'assign.tasks')->firstOrFail();
        $customRole->permissions()->sync([$assignPermission->id]);

        $user->syncRoles([
            ...$user->roles()->pluck('roles.id')->all(),
            $customRole->id,
        ]);

        $this->assertTrue($user->hasRole('sales'));
        $this->assertTrue($user->hasRole('team-lead'));
        $this->assertTrue($user->canAssignTasks());
    }

    public function test_sales_rep_cannot_access_user_management(): void
    {
        $salesRep = User::factory()->create();

        $response = $this->actingAs($salesRep)->get(route('users.index'));

        $response->assertForbidden();
    }
}
