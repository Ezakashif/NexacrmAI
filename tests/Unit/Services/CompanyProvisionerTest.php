<?php

namespace Tests\Unit\Services;

use App\Models\Company;
use App\Models\Plan;
use App\Models\Role;
use App\Models\User;
use App\Services\CompanyProvisioner;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CompanyProvisionerTest extends TestCase
{
    use RefreshDatabase;

    private CompanyProvisioner $provisioner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
        $this->provisioner = app(CompanyProvisioner::class);
    }

    public function test_provision_creates_company_with_slug_from_name(): void
    {
        $result = $this->provisioner->provision([
            'name' => 'Acme CRM',
        ]);

        $company = $result['company'];

        $this->assertInstanceOf(Company::class, $company);
        $this->assertSame('Acme CRM', $company->name);
        $this->assertSame('acme-crm', $company->slug);
        $this->assertNull($result['admin']);

        $this->assertTrue(
            Role::withoutCompanyScope()
                ->where('company_id', $company->id)
                ->where('slug', 'admin')
                ->exists()
        );
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'company.created',
        ]);
    }

    public function test_provision_creates_admin_and_sets_owner(): void
    {
        $result = $this->provisioner->provision([
            'name' => 'Beta Corp',
            'slug' => 'beta',
            'admin_name' => 'Beta Admin',
            'admin_email' => 'admin@beta.test',
            'admin_password' => 'SecurePass1!',
        ]);

        $company = $result['company'];
        $admin = $result['admin'];

        $this->assertNotNull($admin);
        $this->assertSame('admin@beta.test', $admin->email);
        $this->assertSame($company->id, $admin->company_id);
        $this->assertTrue($admin->hasRole('admin'));
        $this->assertTrue(Hash::check('SecurePass1!', $admin->password));
        $this->assertNotNull($admin->email_verified_at);

        $company->refresh();
        $this->assertSame($admin->id, $company->owner_id);
        $this->assertSame('admin@beta.test', $company->email);
    }

    public function test_provision_can_leave_admin_email_unverified(): void
    {
        $result = $this->provisioner->provision([
            'name' => 'Verify Later Co',
            'admin_name' => 'Unverified Admin',
            'admin_email' => 'unverified@example.com',
            'admin_password' => 'SecurePass1!',
            'mark_admin_email_verified' => false,
        ]);

        $this->assertNull($result['admin']->email_verified_at);
    }

    public function test_provision_assigns_default_plan_and_trial(): void
    {
        $defaultPlan = Plan::default();

        $result = $this->provisioner->provision([
            'name' => 'Trial Co',
            'subscription_status' => Company::SUBSCRIPTION_TRIAL,
        ]);

        $company = $result['company'];

        $this->assertNotNull($defaultPlan);
        $this->assertSame($defaultPlan->id, $company->plan_id);
        $this->assertSame(Company::SUBSCRIPTION_TRIAL, $company->subscription_status);
        $this->assertNotNull($company->trial_ends_at);
        $this->assertTrue($company->trial_ends_at->isFuture());
    }

    public function test_provision_generates_unique_slug_when_taken(): void
    {
        Company::factory()->create(['slug' => 'shared-slug']);

        $result = $this->provisioner->provision([
            'name' => 'Shared Slug',
            'slug' => 'shared-slug',
        ]);

        $this->assertSame('shared-slug-1', $result['company']->slug);
    }

    public function test_provision_respects_explicit_plan_and_status(): void
    {
        $plan = Plan::factory()->create(['is_default' => false]);

        $result = $this->provisioner->provision([
            'name' => 'Custom Plan Co',
            'plan_id' => $plan->id,
            'status' => Company::STATUS_SUSPENDED,
            'subscription_status' => Company::SUBSCRIPTION_ACTIVE,
            'trial_ends_at' => null,
        ]);

        $company = $result['company'];

        $this->assertSame($plan->id, $company->plan_id);
        $this->assertSame(Company::STATUS_SUSPENDED, $company->status);
        $this->assertSame(Company::SUBSCRIPTION_ACTIVE, $company->subscription_status);
        $this->assertNull($company->trial_ends_at);
    }

    public function test_provision_generates_random_password_when_admin_password_blank(): void
    {
        $result = $this->provisioner->provision([
            'name' => 'Auto Pass Co',
            'admin_name' => 'Auto Admin',
            'admin_email' => 'auto@example.com',
            'admin_password' => '',
        ]);

        $admin = $result['admin'];

        $this->assertNotNull($admin);
        $this->assertNotEmpty($admin->password);
    }
}
