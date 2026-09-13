<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Role;
use App\Models\User;
use App\Services\RbacRoleSynchronizer;
use App\Services\WebsiteLeadService;
use App\Support\CurrentCompany;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class TenancyPhase1EIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    public function test_policy_denies_cross_company_customer_access(): void
    {
        $default = Company::default();
        $other = Company::factory()->create();
        app(RbacRoleSynchronizer::class)->syncDefaultRolesForCompany($other);

        $admin = User::factory()->admin()->create(['company_id' => $default->id]);
        $foreignCustomer = new Customer([
            'created_by' => $admin->id,
            'name' => 'Foreign Customer',
            'email' => 'foreign-customer@example.com',
            'status' => 'active',
        ]);
        $foreignCustomer->company_id = $other->id;
        $foreignCustomer->save();

        $this->assertFalse($admin->can('view', $foreignCustomer));

        $this->actingAs($admin)
            ->get(route('customers.show', $foreignCustomer))
            ->assertNotFound();
    }

    public function test_cannot_assign_lead_to_user_from_another_company(): void
    {
        $default = Company::default();
        $other = Company::factory()->create();
        app(RbacRoleSynchronizer::class)->syncDefaultRolesForCompany($other);

        $admin = User::factory()->admin()->create(['company_id' => $default->id]);
        $foreignUser = User::factory()->create(['company_id' => $other->id]);

        $lead = Lead::factory()->create([
            'company_id' => $default->id,
            'created_by' => $admin->id,
            'assigned_to' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->put(route('leads.update', $lead), [
                'name' => $lead->name,
                'email' => $lead->email,
                'status' => $lead->status,
                'assigned_to' => $foreignUser->id,
            ])
            ->assertSessionHasErrors('assigned_to');
    }

    public function test_report_employee_filter_rejects_foreign_user_id(): void
    {
        $default = Company::default();
        $other = Company::factory()->create();
        app(RbacRoleSynchronizer::class)->syncDefaultRolesForCompany($other);

        $admin = User::factory()->admin()->create(['company_id' => $default->id]);
        $foreignUser = User::factory()->create(['company_id' => $other->id]);

        $this->actingAs($admin)
            ->get(route('reports.index', ['employee_id' => $foreignUser->id]))
            ->assertSessionHasErrors('employee_id');
    }

    public function test_website_lead_attaches_to_owner_company(): void
    {
        $default = Company::default();
        $admin = User::factory()->admin()->create([
            'company_id' => $default->id,
            'email' => 'webhook-owner@example.com',
        ]);

        Config::set('website_leads.created_by_email', $admin->email);

        app(CurrentCompany::class)->clear();

        $lead = app(WebsiteLeadService::class)->create([
            'name' => 'Webhook Lead',
            'email' => 'webhook-lead@example.com',
            'phone' => '555-0100',
        ]);

        $this->assertSame($default->id, $lead->company_id);
        $this->assertSame($admin->id, $lead->created_by);
    }

    public function test_activity_logger_copies_subject_company_id(): void
    {
        $company = Company::default();
        $user = User::factory()->admin()->create(['company_id' => $company->id]);
        $lead = Lead::factory()->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
            'assigned_to' => $user->id,
        ]);

        app(CurrentCompany::class)->clear();

        $log = \App\Services\ActivityLogger::log('lead.updated', $lead, ['name' => $lead->name], $user->id);

        $this->assertSame($company->id, $log->company_id);
    }

    public function test_super_admin_activity_logger_keeps_platform_company_id_null(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $company = Company::default();

        app(CurrentCompany::class)->clear();

        $log = \App\Services\ActivityLogger::log('company.updated', $company, [
            'name' => $company->name,
            'slug' => $company->slug,
        ], $superAdmin->id);

        $this->assertNull($log->company_id);
        $this->assertSame($company->id, $log->properties['company_id'] ?? null);
        $this->assertSame($company->name, $log->properties['company_name'] ?? null);
    }

    public function test_user_email_must_be_globally_unique(): void
    {
        $default = Company::default();
        $other = Company::factory()->create();
        app(RbacRoleSynchronizer::class)->syncDefaultRolesForCompany($other);

        User::factory()->create([
            'company_id' => $other->id,
            'email' => 'shared@example.com',
        ]);

        $admin = User::factory()->admin()->create(['company_id' => $default->id]);
        $salesRole = Role::withoutCompanyScope()
            ->where('company_id', $default->id)
            ->where('slug', 'sales')
            ->firstOrFail();

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'name' => 'Local Shared',
                'email' => 'shared@example.com',
                'password' => 'SecurePass1!',
                'password_confirmation' => 'SecurePass1!',
                'roles' => [$salesRole->id],
                'status' => 'active',
            ])
            ->assertSessionHasErrors('email');

        $this->assertSame(
            1,
            User::withoutCompanyScope()->where('email', 'shared@example.com')->count()
        );
    }
}
