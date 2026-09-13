<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\Role;
use App\Models\Scopes\CompanyScope;
use App\Models\Task;
use App\Models\User;
use App\Services\RbacRoleSynchronizer;
use Database\Seeders\DemoDataSeeder;
use Database\Seeders\PlanSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class DemoDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seeder_is_idempotent_and_isolates_tenant(): void
    {
        $this->seed(RbacSeeder::class);
        $this->seed(PlanSeeder::class);

        $this->seed(DemoDataSeeder::class);
        $this->seed(DemoDataSeeder::class);

        $company = Company::query()->where('slug', DemoDataSeeder::COMPANY_SLUG)->first();
        $this->assertNotNull($company);
        $this->assertSame(DemoDataSeeder::COMPANY_NAME, $company->name);
        $this->assertSame(1, Company::query()->where('slug', DemoDataSeeder::COMPANY_SLUG)->count());

        $emails = collect(DemoDataSeeder::USERS)->pluck('email')->all();
        $users = User::withoutGlobalScope(CompanyScope::class)
            ->whereIn('email', $emails)
            ->get();
        $this->assertCount(3, $users);
        $this->assertTrue($users->every(fn (User $user) => $user->company_id === $company->id));

        $admin = $users->firstWhere('email', DemoDataSeeder::USERS['admin']['email']);
        $manager = $users->firstWhere('email', DemoDataSeeder::USERS['manager']['email']);
        $sales = $users->firstWhere('email', DemoDataSeeder::USERS['sales']['email']);

        $this->assertTrue($admin->hasPermission('view.users') || $admin->roles()->where('slug', 'admin')->exists());
        $this->assertTrue($manager->roles()->where('slug', DemoDataSeeder::SALES_MANAGER_ROLE_SLUG)->exists());
        $this->assertTrue($manager->canViewAllLeads());
        $this->assertTrue($sales->roles()->where('slug', 'sales')->exists());
        $this->assertFalse($sales->canViewAllLeads());

        $leadCount = Lead::withoutGlobalScope(CompanyScope::class)->where('company_id', $company->id)->count();
        $customerCount = Customer::withoutGlobalScope(CompanyScope::class)->where('company_id', $company->id)->count();
        $taskCount = Task::withoutGlobalScope(CompanyScope::class)->where('company_id', $company->id)->count();
        $activityCount = LeadActivity::withoutGlobalScope(CompanyScope::class)->where('company_id', $company->id)->count();

        $this->assertSame(18, $leadCount);
        $this->assertSame(8, $customerCount);
        $this->assertSame(19, $taskCount);
        $this->assertGreaterThanOrEqual(40, $activityCount);

        $this->assertSame(
            1,
            Role::withoutGlobalScope(CompanyScope::class)
                ->where('company_id', $company->id)
                ->where('slug', DemoDataSeeder::SALES_MANAGER_ROLE_SLUG)
                ->count()
        );

        // Default company (if present) is untouched by demo lead emails.
        $default = Company::query()->where('slug', Company::DEFAULT_SLUG)->first();
        if ($default) {
            $this->assertSame(
                0,
                Lead::withoutGlobalScope(CompanyScope::class)
                    ->where('company_id', $default->id)
                    ->where('email', 'like', '%@%.demo.algoscrm.test')
                    ->count()
            );
        }
    }

    public function test_demo_users_can_authenticate(): void
    {
        $this->seed(RbacSeeder::class);
        $this->seed(PlanSeeder::class);
        $this->seed(DemoDataSeeder::class);

        foreach (DemoDataSeeder::USERS as $definition) {
            $this->post('/login', [
                'email' => $definition['email'],
                'password' => DemoDataSeeder::demoPassword(),
            ])->assertRedirect(route('dashboard'));

            $this->post('/logout');
        }
    }

    public function test_demo_seeder_fails_when_password_is_missing(): void
    {
        $this->seed(RbacSeeder::class);
        $this->seed(PlanSeeder::class);
        config(['demo.seed_password' => null]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('DEMO_SEED_PASSWORD');

        $this->seed(DemoDataSeeder::class);
    }

    public function test_demo_seeder_aborts_on_email_collision_with_another_company(): void
    {
        $this->seed(RbacSeeder::class);
        $this->seed(PlanSeeder::class);

        $other = Company::factory()->create(['slug' => 'existing-tenant']);
        app(RbacRoleSynchronizer::class)->syncDefaultRolesForCompany($other);
        User::factory()->create([
            'company_id' => $other->id,
            'email' => DemoDataSeeder::USERS['admin']['email'],
        ]);

        try {
            $this->seed(DemoDataSeeder::class);
            $this->fail('Expected email collision to abort the seeder.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString(DemoDataSeeder::USERS['admin']['email'], $e->getMessage());
            $this->assertStringContainsString('already belongs to another company', $e->getMessage());
        }

        $colliding = User::withoutGlobalScope(CompanyScope::class)
            ->where('email', DemoDataSeeder::USERS['admin']['email'])
            ->firstOrFail();
        $this->assertSame($other->id, $colliding->company_id);
        $this->assertSame(0, Company::query()->where('slug', DemoDataSeeder::COMPANY_SLUG)->count());
    }
}
