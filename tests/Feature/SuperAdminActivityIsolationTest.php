<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Support\CurrentCompany;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminActivityIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    public function test_super_admin_login_is_not_attached_to_tenant_company(): void
    {
        $superAdmin = User::factory()->superAdmin()->create([
            'email' => 'superadmin@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->post('/login', [
            'email' => 'superadmin@example.com',
            'password' => 'password',
        ])->assertRedirect(route('superadmin.dashboard', absolute: false));

        $log = ActivityLog::withoutCompanyScope()
            ->where('user_id', $superAdmin->id)
            ->where('action', 'user.login')
            ->first();

        $this->assertNotNull($log);
        $this->assertNull($log->company_id);
    }

    public function test_super_admin_company_actions_stay_off_tenant_activity_log(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $company = Company::factory()->create(['name' => 'Acme CRM', 'slug' => 'acme']);
        $admin = User::factory()->admin()->create(['company_id' => $company->id]);

        $this->actingAs($superAdmin)
            ->put(route('superadmin.companies.update', $company), [
                'name' => 'Acme CRM Updated',
                'slug' => 'acme',
                'status' => Company::STATUS_ACTIVE,
                'subscription_status' => Company::SUBSCRIPTION_ACTIVE,
            ])
            ->assertRedirect(route('superadmin.companies.show', $company));

        $platformLog = ActivityLog::withoutCompanyScope()
            ->where('action', 'company.updated')
            ->where('user_id', $superAdmin->id)
            ->first();

        $this->assertNotNull($platformLog);
        $this->assertNull($platformLog->company_id);
        $this->assertSame($company->id, $platformLog->properties['company_id'] ?? null);

        $this->assertFalse(
            ActivityLog::query()->forTenant()->whereKey($platformLog->id)->exists()
        );

        $this->actingAs($admin)
            ->get(route('activity-logs.index'))
            ->assertOk()
            ->assertDontSee('Updated company Acme CRM Updated');
    }

    public function test_super_admin_dashboard_shows_platform_activity_only(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $company = Company::default();
        $admin = User::factory()->admin()->create(['company_id' => $company->id]);

        ActivityLogger::log('company.status_changed', $company, [
            'from' => 'active',
            'to' => 'suspended',
            'name' => $company->name,
        ], $superAdmin->id);

        ActivityLogger::log('customer.created', null, [
            'name' => 'Tenant Customer',
            'company_id' => $company->id,
        ], $admin->id);

        $this->actingAs($superAdmin)
            ->get(route('superadmin.dashboard'))
            ->assertOk()
            ->assertSee('Company status changed')
            ->assertDontSee('Customer created');
    }

    public function test_tenant_activity_logger_still_copies_company_id(): void
    {
        $company = Company::default();
        $admin = User::factory()->admin()->create(['company_id' => $company->id]);

        app(CurrentCompany::class)->set($company);

        $log = ActivityLogger::log('profile.updated', $admin, [
            'name' => $admin->name,
        ], $admin->id);

        $this->assertSame($company->id, $log->company_id);
    }

    public function test_historical_super_admin_rows_are_hidden_from_tenant_log(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $company = Company::default();
        $admin = User::factory()->admin()->create(['company_id' => $company->id]);

        // Legacy row incorrectly attached to the tenant company.
        ActivityLog::withoutCompanyScope()->create([
            'company_id' => $company->id,
            'user_id' => $superAdmin->id,
            'action' => 'user.login',
            'properties' => null,
        ]);

        ActivityLog::withoutCompanyScope()->create([
            'company_id' => $company->id,
            'user_id' => $admin->id,
            'action' => 'customer.created',
            'properties' => ['name' => 'Visible Customer'],
        ]);

        app(CurrentCompany::class)->set($company);

        $this->assertFalse(
            ActivityLog::query()
                ->forTenant()
                ->where('user_id', $superAdmin->id)
                ->where('action', 'user.login')
                ->exists()
        );

        $this->actingAs($admin)
            ->get(route('activity-logs.index'))
            ->assertOk()
            ->assertSee('Visible Customer')
            ->assertSee('Created customer Visible Customer');
    }
}
