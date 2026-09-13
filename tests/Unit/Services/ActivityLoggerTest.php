<?php

namespace Tests\Unit\Services;

use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\Lead;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Support\CurrentCompany;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLoggerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    public function test_tenant_actor_logs_company_id_from_subject(): void
    {
        $company = Company::factory()->create(['name' => 'Tenant Co']);
        $admin = User::factory()->admin()->create(['company_id' => $company->id]);
        $lead = Lead::factory()->create([
            'company_id' => $company->id,
            'created_by' => $admin->id,
            'assigned_to' => $admin->id,
        ]);

        app(CurrentCompany::class)->set($company);

        $log = ActivityLogger::log('lead.updated', $lead, ['status' => 'qualified'], $admin->id);

        $this->assertSame($company->id, $log->company_id);
        $this->assertSame('lead.updated', $log->action);
        $this->assertSame($admin->id, $log->user_id);
        $this->assertDatabaseHas('activity_logs', [
            'id' => $log->id,
            'company_id' => $company->id,
        ]);
    }

    public function test_tenant_actor_falls_back_to_current_company_when_subject_has_no_company(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->create(['company_id' => $company->id]);

        app(CurrentCompany::class)->set($company);

        $log = ActivityLogger::log('profile.updated', $admin, ['name' => $admin->name], $admin->id);

        $this->assertSame($company->id, $log->company_id);
    }

    public function test_super_admin_actor_keeps_company_id_null_and_stores_related_company_in_properties(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $company = Company::factory()->create(['name' => 'Managed Co']);

        $log = ActivityLogger::log('company.updated', $company, [
            'name' => 'Managed Co Updated',
        ], $superAdmin->id);

        $this->assertNull($log->company_id);
        $this->assertSame($company->id, $log->properties['company_id']);
        $this->assertSame('Managed Co', $log->properties['company_name']);

        $this->assertTrue(
            ActivityLog::withoutCompanyScope()->whereKey($log->id)->whereNull('company_id')->exists()
        );
    }

    public function test_super_admin_actor_on_tenant_subject_stores_company_metadata_without_attaching_tenant(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->create(['company_id' => $company->id]);
        $lead = Lead::factory()->create([
            'company_id' => $company->id,
            'created_by' => $admin->id,
            'assigned_to' => $admin->id,
        ]);

        $log = ActivityLogger::log('lead.viewed', $lead, [], $superAdmin->id);

        $this->assertNull($log->company_id);
        $this->assertSame($company->id, $log->properties['company_id']);
    }

    public function test_super_admin_platform_log_is_hidden_from_tenant_scoped_queries(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $company = Company::factory()->create();

        $log = ActivityLogger::log('company.status_changed', $company, [
            'from' => 'active',
            'to' => 'suspended',
        ], $superAdmin->id);

        app(CurrentCompany::class)->set($company);

        $this->assertFalse(
            ActivityLog::query()->forTenant()->whereKey($log->id)->exists()
        );
    }
}
