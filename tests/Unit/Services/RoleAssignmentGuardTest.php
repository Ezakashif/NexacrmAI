<?php

namespace Tests\Unit\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\RoleAssignmentGuard;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RoleAssignmentGuardTest extends TestCase
{
    use RefreshDatabase;

    private RoleAssignmentGuard $guard;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
        $this->guard = app(RoleAssignmentGuard::class);
    }

    public function test_admin_can_assign_any_roles_including_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $adminRoleId = Role::query()
            ->where('company_id', $admin->company_id)
            ->where('slug', 'admin')
            ->value('id');
        $salesRoleId = Role::query()
            ->where('company_id', $admin->company_id)
            ->where('slug', 'sales')
            ->value('id');

        $this->guard->assertCanAssignRoles($admin, [$adminRoleId, $salesRoleId]);

        $this->expectNotToPerformAssertions();
    }

    public function test_non_admin_cannot_assign_admin_role(): void
    {
        $sales = User::factory()->create();
        $adminRoleId = Role::query()
            ->where('company_id', $sales->company_id)
            ->where('slug', 'admin')
            ->value('id');

        try {
            $this->guard->assertCanAssignRoles($sales, [$adminRoleId]);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('roles', $exception->errors());
            $this->assertSame('Only company admins can assign the Admin role.', $exception->errors()['roles'][0]);
        }
    }

    public function test_non_admin_can_assign_non_admin_roles(): void
    {
        $sales = User::factory()->create();
        $salesRoleId = Role::query()
            ->where('company_id', $sales->company_id)
            ->where('slug', 'sales')
            ->value('id');

        $this->guard->assertCanAssignRoles($sales, [$salesRoleId]);

        $this->expectNotToPerformAssertions();
    }

    public function test_admin_can_grant_any_permissions(): void
    {
        $admin = User::factory()->admin()->create();
        $permissionIds = Permission::query()
            ->whereIn('slug', ['delete.users', 'delete.customers'])
            ->pluck('id')
            ->all();

        $result = $this->guard->filterAssignablePermissions($admin, $permissionIds);

        $this->assertEqualsCanonicalizing($permissionIds, $result);
    }

    public function test_non_admin_cannot_grant_permissions_they_do_not_have(): void
    {
        $sales = User::factory()->create();
        $salesRole = Role::query()
            ->where('company_id', $sales->company_id)
            ->where('slug', 'sales')
            ->firstOrFail();

        $salesRole->permissions()->syncWithoutDetaching(
            Permission::query()->whereIn('slug', ['view.leads', 'view.roles', 'create.roles'])->pluck('id')
        );
        $sales->refresh()->unsetRelation('roles');

        $forbiddenPermissionId = Permission::query()->where('slug', 'delete.users')->value('id');

        try {
            $this->guard->filterAssignablePermissions($sales, [$forbiddenPermissionId]);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('permissions', $exception->errors());
            $this->assertSame('You cannot grant permissions you do not have.', $exception->errors()['permissions'][0]);
        }
    }

    public function test_non_admin_can_grant_permissions_they_hold(): void
    {
        $sales = User::factory()->create();
        $salesRole = Role::query()
            ->where('company_id', $sales->company_id)
            ->where('slug', 'sales')
            ->firstOrFail();

        $allowedPermissionIds = Permission::query()
            ->whereIn('slug', ['view.leads', 'create.leads'])
            ->pluck('id')
            ->all();

        $salesRole->permissions()->syncWithoutDetaching($allowedPermissionIds);
        $sales->refresh()->unsetRelation('roles');

        $result = $this->guard->filterAssignablePermissions($sales, $allowedPermissionIds);

        $this->assertEqualsCanonicalizing($allowedPermissionIds, $result);
    }

    public function test_filter_assignable_permissions_returns_empty_for_null_or_empty_input(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertSame([], $this->guard->filterAssignablePermissions($admin, null));
        $this->assertSame([], $this->guard->filterAssignablePermissions($admin, []));
    }
}
