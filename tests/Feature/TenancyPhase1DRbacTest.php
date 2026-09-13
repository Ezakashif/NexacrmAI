<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\RbacRoleSynchronizer;
use App\Support\CurrentCompany;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenancyPhase1DRbacTest extends TestCase
{
    use RefreshDatabase;

    public function test_rbac_seeder_creates_default_roles_for_default_company(): void
    {
        $this->seed(RbacSeeder::class);

        $company = Company::default();

        $this->assertNotNull($company);
        $this->assertDatabaseHas('roles', [
            'company_id' => $company->id,
            'slug' => 'admin',
            'is_system' => true,
        ]);
        $this->assertDatabaseHas('roles', [
            'company_id' => $company->id,
            'slug' => 'sales',
            'is_system' => false,
        ]);
    }

    public function test_permissions_sync_creates_default_roles_for_every_company(): void
    {
        $this->seed(RbacSeeder::class);

        $other = Company::factory()->create(['name' => 'Acme CRM', 'slug' => 'acme']);

        $this->artisan('permissions:sync')->assertSuccessful();

        foreach ([Company::default(), $other] as $company) {
            $this->assertTrue(
                Role::withoutCompanyScope()
                    ->where('company_id', $company->id)
                    ->where('slug', 'admin')
                    ->where('is_system', true)
                    ->exists()
            );
            $this->assertTrue(
                Role::withoutCompanyScope()
                    ->where('company_id', $company->id)
                    ->where('slug', 'sales')
                    ->exists()
            );
        }

        $this->assertSame(
            2,
            Role::withoutCompanyScope()->where('slug', 'admin')->where('is_system', true)->count()
        );
    }

    public function test_role_slug_uniqueness_is_per_company(): void
    {
        $this->seed(RbacSeeder::class);

        $default = Company::default();
        $other = Company::factory()->create();

        app(RbacRoleSynchronizer::class)->syncDefaultRolesForCompany($other);

        $admin = User::factory()->admin()->create(['company_id' => $default->id]);

        $response = $this->actingAs($admin)->post(route('roles.store'), [
            'name' => 'Support',
            'slug' => 'support',
            'description' => null,
            'permissions' => Permission::query()->where('slug', 'view.leads')->pluck('id')->all(),
        ]);

        $response->assertRedirect(route('roles.index'));

        $this->assertDatabaseHas('roles', [
            'company_id' => $default->id,
            'slug' => 'support',
        ]);

        // Same slug is allowed for another company.
        Role::withoutCompanyScope()->create([
            'company_id' => $other->id,
            'name' => 'Support',
            'slug' => 'support',
            'is_system' => false,
        ]);

        $this->assertSame(
            2,
            Role::withoutCompanyScope()->where('slug', 'support')->count()
        );
    }

    public function test_company_admin_only_sees_own_company_roles(): void
    {
        $this->seed(RbacSeeder::class);

        $default = Company::default();
        $other = Company::factory()->create();
        app(RbacRoleSynchronizer::class)->syncDefaultRolesForCompany($other);

        Role::withoutCompanyScope()->create([
            'company_id' => $other->id,
            'name' => 'Other Only',
            'slug' => 'other_only',
            'is_system' => false,
        ]);

        $admin = User::factory()->admin()->create(['company_id' => $default->id]);

        $this->actingAs($admin)
            ->get(route('roles.index'))
            ->assertOk()
            ->assertSee('Administrator')
            ->assertDontSee('Other Only');
    }

    public function test_user_cannot_be_assigned_role_from_another_company(): void
    {
        $this->seed(RbacSeeder::class);

        $default = Company::default();
        $other = Company::factory()->create();
        app(RbacRoleSynchronizer::class)->syncDefaultRolesForCompany($other);

        $foreignRole = Role::withoutCompanyScope()
            ->where('company_id', $other->id)
            ->where('slug', 'sales')
            ->firstOrFail();

        $admin = User::factory()->admin()->create(['company_id' => $default->id]);
        $target = User::factory()->create(['company_id' => $default->id]);

        $response = $this->actingAs($admin)->put(route('users.update', $target), [
            'name' => $target->name,
            'email' => $target->email,
            'roles' => [$foreignRole->id],
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors('roles.0');
        $this->assertFalse($target->fresh()->roles()->where('roles.id', $foreignRole->id)->exists());
    }

    public function test_sync_roles_ignores_foreign_company_role_ids(): void
    {
        $this->seed(RbacSeeder::class);

        $default = Company::default();
        $other = Company::factory()->create();
        app(RbacRoleSynchronizer::class)->syncDefaultRolesForCompany($other);

        $localSales = Role::withoutCompanyScope()
            ->where('company_id', $default->id)
            ->where('slug', 'sales')
            ->firstOrFail();
        $foreignAdmin = Role::withoutCompanyScope()
            ->where('company_id', $other->id)
            ->where('slug', 'admin')
            ->firstOrFail();

        $user = User::factory()->create(['company_id' => $default->id]);
        $user->syncRoles([$localSales->id, $foreignAdmin->id]);

        $this->assertTrue($user->fresh()->hasRole('sales'));
        $this->assertFalse($user->fresh()->hasRole('admin'));
    }

    public function test_legacy_role_sync_uses_users_company_roles(): void
    {
        $this->seed(RbacSeeder::class);

        $other = Company::factory()->create();
        app(RbacRoleSynchronizer::class)->syncDefaultRolesForCompany($other);

        app(CurrentCompany::class)->clear();

        $user = User::factory()->create([
            'company_id' => $other->id,
            'role' => 'admin',
        ]);

        $attached = $user->roles()->first();

        $this->assertNotNull($attached);
        $this->assertSame($other->id, $attached->company_id);
        $this->assertSame('admin', $attached->slug);
    }
}
