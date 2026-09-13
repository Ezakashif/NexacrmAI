<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    public function test_admin_can_view_roles_index(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('roles.index'));

        $response->assertOk()
            ->assertSee('Roles')
            ->assertSee('Administrator');

        $this->assertDatabaseHas('roles', ['slug' => 'admin', 'is_system' => true]);
        $this->assertDatabaseHas('roles', ['slug' => 'sales', 'is_system' => false]);
        $this->assertDatabaseMissing('roles', ['slug' => 'manager']);
    }

    public function test_admin_can_create_role_with_permissions(): void
    {
        $admin = User::factory()->admin()->create();
        $permissionIds = Permission::query()->whereIn('slug', ['view.leads', 'view.customers'])->pluck('id')->all();

        $response = $this->actingAs($admin)->post(route('roles.store'), [
            'name' => 'Support Agent',
            'slug' => 'support_agent',
            'description' => 'Read-only support access',
            'permissions' => $permissionIds,
        ]);

        $response->assertRedirect(route('roles.index'));

        $role = Role::query()->where('slug', 'support_agent')->first();

        $this->assertNotNull($role);
        $this->assertSame('Support Agent', $role->name);
        $this->assertCount(2, $role->permissions);
    }

    public function test_custom_role_restricts_assigned_user(): void
    {
        $admin = User::factory()->admin()->create();

        $role = Role::create([
            'name' => 'Leads Only',
            'slug' => 'leads_only',
            'description' => null,
            'is_system' => false,
        ]);

        $role->permissions()->sync(
            Permission::query()->where('slug', 'view.leads')->pluck('id')
        );

        $user = User::factory()->create();
        $user->syncRoles([$role->id]);

        $this->assertTrue($user->hasPermission('view.leads'));
        $this->assertFalse($user->hasPermission('view.customers'));

        $response = $this->actingAs($user)->get(route('customers.index'));

        $response->assertForbidden();
    }

    public function test_system_role_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $role = Role::query()->where('slug', 'admin')->firstOrFail();

        $response = $this->actingAs($admin)->delete(route('roles.destroy', $role));

        $response->assertRedirect();
        $response->assertSessionHasErrors('error');
        $this->assertDatabaseHas('roles', ['id' => $role->id]);
    }

    public function test_sales_rep_cannot_manage_roles(): void
    {
        $salesRep = User::factory()->create();

        $response = $this->actingAs($salesRep)->get(route('roles.index'));

        $response->assertForbidden();
    }

    public function test_permissions_page_is_not_available(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/permissions');

        $response->assertNotFound();
    }
}
