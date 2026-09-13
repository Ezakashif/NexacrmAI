<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Lead;
use App\Models\Permission;
use App\Models\Plan;
use App\Models\Role;
use App\Models\User;
use App\Services\SuperAdmin\ImpersonationService;
use App\Services\SuperAdmin\PlatformSettingsService;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HighPriorityAuditFixesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    public function test_unverified_user_cannot_access_crm_routes(): void
    {
        $user = User::factory()->unverified()->admin()->create();

        $this->actingAs($user)
            ->get(route('leads.index'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_non_admin_cannot_assign_admin_role_to_self(): void
    {
        $sales = User::factory()->create();
        $salesRole = Role::query()->where('slug', 'sales')->firstOrFail();
        $adminRole = Role::query()->where('slug', 'admin')->firstOrFail();

        // Grant user management without being admin.
        $salesRole->permissions()->syncWithoutDetaching(
            Permission::query()->whereIn('slug', [
                'view.users',
                'create.users',
                'update.users',
                'view.roles',
                'update.roles',
                'create.roles',
            ])->pluck('id')
        );
        $sales->refresh();
        $sales->unsetRelation('roles');

        $this->actingAs($sales)
            ->put(route('users.update', $sales), [
                'name' => $sales->name,
                'email' => $sales->email,
                'roles' => [$adminRole->id],
                'status' => 'active',
            ])
            ->assertSessionHasErrors('roles');

        $this->assertFalse($sales->fresh()->hasRole('admin'));
    }

    public function test_non_admin_cannot_grant_permissions_they_do_not_have(): void
    {
        $sales = User::factory()->create();
        $salesRole = Role::query()->where('slug', 'sales')->firstOrFail();

        $salesRole->permissions()->syncWithoutDetaching(
            Permission::query()->whereIn('slug', [
                'view.roles',
                'create.roles',
                'update.roles',
                'view.leads',
            ])->pluck('id')
        );

        $forbiddenPermissionId = Permission::query()->where('slug', 'delete.users')->value('id');

        $this->actingAs($sales->fresh())
            ->post(route('roles.store'), [
                'name' => 'Escalated',
                'slug' => 'escalated',
                'permissions' => [$forbiddenPermissionId],
            ])
            ->assertSessionHasErrors('permissions');

        $this->assertDatabaseMissing('roles', ['slug' => 'escalated']);
    }

    public function test_plan_limit_blocks_creating_extra_users(): void
    {
        $plan = Plan::factory()->create([
            'max_users' => 1,
            'max_leads' => 100,
            'max_customers' => 100,
            'is_default' => false,
        ]);

        $company = Company::factory()->create(['plan_id' => $plan->id]);
        $admin = User::factory()->admin()->create(['company_id' => $company->id]);
        $salesRoleId = Role::query()
            ->where('company_id', $company->id)
            ->where('slug', 'sales')
            ->value('id');

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'name' => 'Extra User',
                'email' => 'extra@example.com',
                'password' => 'SecurePass1!',
                'password_confirmation' => 'SecurePass1!',
                'roles' => [$salesRoleId],
                'status' => 'active',
            ])
            ->assertSessionHasErrors('users');

        $this->assertDatabaseMissing('users', ['email' => 'extra@example.com']);
    }

    public function test_plan_limit_blocks_creating_extra_leads(): void
    {
        $plan = Plan::factory()->create([
            'max_users' => 100,
            'max_leads' => 1,
            'max_customers' => 100,
            'is_default' => false,
        ]);

        $company = Company::factory()->create(['plan_id' => $plan->id]);
        $admin = User::factory()->admin()->create(['company_id' => $company->id]);

        Lead::factory()->create([
            'company_id' => $company->id,
            'created_by' => $admin->id,
            'assigned_to' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->post(route('leads.store'), [
                'name' => 'Over Limit Lead',
                'status' => 'new',
            ])
            ->assertSessionHasErrors('leads');
    }

    public function test_is_super_admin_is_not_mass_assignable(): void
    {
        $user = User::factory()->create();

        $user->fill([
            'is_super_admin' => true,
            'status' => 'suspended',
            'role' => 'admin',
        ])->save();

        $user->refresh();

        $this->assertFalse($user->is_super_admin);
        $this->assertSame('active', $user->status);
        $this->assertSame('user', $user->role);
    }

    public function test_suspended_and_expired_login_messages_differ(): void
    {
        $suspended = Company::factory()->suspended()->create(['slug' => 'suspended-co']);
        $expired = Company::factory()->expired()->create(['slug' => 'expired-co']);

        User::factory()->admin()->create([
            'company_id' => $suspended->id,
            'email' => 'suspended@example.com',
        ]);
        User::factory()->admin()->create([
            'company_id' => $expired->id,
            'email' => 'expired@example.com',
        ]);

        $this->post('/login', [
            'email' => 'suspended@example.com',
            'password' => 'password',
        ])->assertSessionHasErrors([
            'email' => 'Your company account is suspended. Please contact support.',
        ]);

        $this->post('/login', [
            'email' => 'expired@example.com',
            'password' => 'password',
        ])->assertSessionHasErrors([
            'email' => 'Your free trial has expired.',
        ]);
    }

    public function test_impersonation_continues_during_maintenance(): void
    {
        app(PlatformSettingsService::class)->setMany(['maintenance_mode' => true]);

        $company = Company::factory()->create();
        $admin = User::factory()->admin()->create(['company_id' => $company->id]);
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->withSession([
                ImpersonationService::SESSION_IMPERSONATOR_ID => $superAdmin->id,
                ImpersonationService::SESSION_IMPERSONATION_LOG_ID => 1,
            ])
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_platform_settings_apply_mail_timezone_and_currency(): void
    {
        app(PlatformSettingsService::class)->setMany([
            'default_timezone' => 'America/New_York',
            'default_currency' => 'eur',
            'mail_from_name' => 'Algos Support',
            'mail_from_address' => 'support@algos.test',
        ]);

        app(PlatformSettingsService::class)->applyBranding();

        $this->assertSame('America/New_York', config('app.timezone'));
        $this->assertSame('EUR', config('app.currency'));
        $this->assertSame('Algos Support', config('mail.from.name'));
        $this->assertSame('support@algos.test', config('mail.from.address'));
    }

    public function test_soft_deleted_lead_fails_exists_in_company_validation(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = Lead::factory()->create([
            'company_id' => $admin->company_id,
            'created_by' => $admin->id,
            'assigned_to' => $admin->id,
            'status' => 'new',
        ]);
        $lead->delete();

        $this->actingAs($admin)
            ->postJson(route('leads.board.update'), [
                'lead_id' => $lead->id,
                'status' => 'contacted',
                'sort_order' => 1,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('lead_id');
    }
}
