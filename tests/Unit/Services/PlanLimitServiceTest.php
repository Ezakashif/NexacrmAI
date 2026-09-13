<?php

namespace Tests\Unit\Services;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Plan;
use App\Models\PlanLimit;
use App\Models\User;
use App\Services\PlanLimitService;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PlanLimitServiceTest extends TestCase
{
    use RefreshDatabase;

    private PlanLimitService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
        $this->service = app(PlanLimitService::class);
    }

    public function test_assert_can_add_user_passes_when_under_max_users(): void
    {
        $plan = Plan::factory()->create([
            'max_users' => 2,
            'max_leads' => 100,
            'max_customers' => 100,
            'is_default' => false,
        ]);
        $company = Company::factory()->create(['plan_id' => $plan->id]);
        User::factory()->admin()->create(['company_id' => $company->id]);

        $this->service->assertCanAddUser($company->fresh());

        $this->expectNotToPerformAssertions();
    }

    public function test_assert_can_add_user_throws_when_at_max_users(): void
    {
        $plan = Plan::factory()->create([
            'max_users' => 1,
            'max_leads' => 100,
            'max_customers' => 100,
            'is_default' => false,
        ]);
        $company = Company::factory()->create(['plan_id' => $plan->id]);
        User::factory()->admin()->create(['company_id' => $company->id]);

        try {
            $this->service->assertCanAddUser($company->fresh());
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('users', $exception->errors());
            $this->assertStringContainsString('maximum of 1 users', $exception->errors()['users'][0]);
        }
    }

    public function test_assert_can_add_user_ignores_super_admin_accounts(): void
    {
        $plan = Plan::factory()->create([
            'max_users' => 1,
            'max_leads' => 100,
            'max_customers' => 100,
            'is_default' => false,
        ]);
        $company = Company::factory()->create(['plan_id' => $plan->id]);

        User::withoutCompanyScope()->create([
            'name' => 'Platform Admin',
            'email' => 'platform@example.com',
            'password' => bcrypt('password'),
            'company_id' => $company->id,
            'is_super_admin' => true,
            'status' => 'active',
        ]);

        $this->service->assertCanAddUser($company->fresh());

        $this->expectNotToPerformAssertions();
    }

    public function test_assert_can_add_lead_throws_when_at_max_leads(): void
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

        $this->expectException(ValidationException::class);
        $this->service->assertCanAddLead($company->fresh());
    }

    public function test_assert_can_add_customer_throws_when_at_max_customers(): void
    {
        $plan = Plan::factory()->create([
            'max_users' => 100,
            'max_leads' => 100,
            'max_customers' => 1,
            'is_default' => false,
        ]);
        $company = Company::factory()->create(['plan_id' => $plan->id]);
        Customer::factory()->create(['company_id' => $company->id]);

        $this->expectException(ValidationException::class);
        $this->service->assertCanAddCustomer($company->fresh());
    }

    public function test_remaining_returns_slots_left_for_metric(): void
    {
        $plan = Plan::factory()->create([
            'max_users' => 5,
            'max_leads' => 10,
            'max_customers' => 3,
            'is_default' => false,
        ]);
        $company = Company::factory()->create(['plan_id' => $plan->id]);
        $admin = User::factory()->admin()->create(['company_id' => $company->id]);

        Lead::factory()->count(4)->create([
            'company_id' => $company->id,
            'created_by' => $admin->id,
            'assigned_to' => $admin->id,
        ]);

        $this->assertSame(4, $this->service->remaining($company->fresh(), 'users'));
        $this->assertSame(6, $this->service->remaining($company->fresh(), 'leads'));
        $this->assertSame(3, $this->service->remaining($company->fresh(), 'customers'));
    }

    public function test_remaining_returns_null_for_unlimited_max_columns(): void
    {
        $plan = Plan::factory()->create([
            'max_users' => null,
            'max_leads' => null,
            'max_customers' => null,
            'is_default' => false,
        ]);
        $company = Company::factory()->create(['plan_id' => $plan->id]);

        $this->assertNull($this->service->remaining($company->fresh(), 'users'));
        $this->assertNull($this->service->remaining($company->fresh(), 'leads'));
    }

    public function test_remaining_returns_null_when_plan_limit_is_unlimited(): void
    {
        $plan = Plan::factory()->create([
            'max_users' => 1,
            'max_leads' => 1,
            'max_customers' => 1,
            'is_default' => false,
        ]);

        PlanLimit::factory()->create([
            'plan_id' => $plan->id,
            'limit_key' => 'users',
            'limit_name' => 'Users',
            'limit_value' => 'unlimited',
        ]);

        $company = Company::factory()->create(['plan_id' => $plan->id]);
        User::factory()->admin()->create(['company_id' => $company->id]);

        $this->assertNull($this->service->remaining($company->fresh(), 'users'));
    }

    public function test_plan_limit_records_take_precedence_over_max_columns(): void
    {
        $plan = Plan::factory()->create([
            'max_users' => 100,
            'max_leads' => 100,
            'max_customers' => 100,
            'is_default' => false,
        ]);

        PlanLimit::factory()->create([
            'plan_id' => $plan->id,
            'limit_key' => 'leads',
            'limit_name' => 'Leads',
            'limit_value' => '2',
        ]);

        $company = Company::factory()->create(['plan_id' => $plan->id]);
        $admin = User::factory()->admin()->create(['company_id' => $company->id]);

        Lead::factory()->count(2)->create([
            'company_id' => $company->id,
            'created_by' => $admin->id,
            'assigned_to' => $admin->id,
        ]);

        $this->assertSame(0, $this->service->remaining($company->fresh(), 'leads'));

        $this->expectException(ValidationException::class);
        $this->service->assertCanAddLead($company->fresh());
    }

    public function test_assert_within_limit_skips_when_company_has_no_plan(): void
    {
        $company = Company::factory()->create(['plan_id' => null]);

        $this->service->assertCanAddUser($company);
        $this->service->assertCanAddLead($company);
        $this->service->assertCanAddCustomer($company);

        $this->expectNotToPerformAssertions();
    }
}
