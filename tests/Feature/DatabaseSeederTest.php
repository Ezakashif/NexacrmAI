<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\EmailTemplate;
use App\Models\Lead;
use App\Models\Permission;
use App\Models\Plan;
use App\Models\Scopes\CompanyScope;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_seed_is_platform_data_only(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(0, User::withoutGlobalScope(CompanyScope::class)->count());
        $this->assertDatabaseMissing('users', ['email' => 'superadmin@example.com']);
        $this->assertDatabaseMissing('users', ['email' => 'admin@example.com']);
        $this->assertDatabaseMissing('users', ['email' => 'sales@example.com']);

        $this->assertSame(3, Plan::query()->whereIn('slug', ['starter', 'professional', 'enterprise'])->count());
        $this->assertTrue(Plan::query()->where('slug', 'starter')->where('is_default', true)->exists());
        $this->assertGreaterThan(0, Permission::query()->count());
        $this->assertGreaterThan(0, EmailTemplate::query()->count());

        $default = Company::query()->where('slug', Company::DEFAULT_SLUG)->first();
        $this->assertNotNull($default);

        $this->assertSame(0, Company::query()->where('slug', DemoDataSeeder::COMPANY_SLUG)->count());
        $this->assertSame(0, Customer::withoutGlobalScope(CompanyScope::class)->count());
        $this->assertSame(0, Lead::withoutGlobalScope(CompanyScope::class)->count());
        $this->assertSame(0, Task::withoutGlobalScope(CompanyScope::class)->count());
    }

    public function test_duplicate_plan_seed_call_is_not_part_of_default_flow(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(3, Plan::query()->whereIn('slug', ['starter', 'professional', 'enterprise'])->count());
        $this->assertSame(1, Plan::query()->where('is_default', true)->count());
    }

    public function test_super_admin_is_created_from_setup_environment_variables(): void
    {
        config([
            'setup.super_admin.name' => 'Platform Owner',
            'setup.super_admin.email' => 'owner@nexacrm.test',
            'setup.super_admin.password' => 'NexaSetup#9x',
        ]);

        $this->seed(DatabaseSeeder::class);

        $user = User::withoutGlobalScope(CompanyScope::class)
            ->where('email', 'owner@nexacrm.test')
            ->first();

        $this->assertNotNull($user);
        $this->assertTrue($user->isSuperAdmin());
        $this->assertNull($user->company_id);
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertTrue(Hash::check('NexaSetup#9x', $user->password));
        $this->assertFalse(Hash::check('password', $user->password));
    }

    public function test_partial_setup_credentials_fail_the_seed(): void
    {
        config([
            'setup.super_admin.email' => 'owner@nexacrm.test',
            'setup.super_admin.password' => null,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('SETUP_SUPERADMIN_EMAIL');

        $this->seed(DatabaseSeeder::class);
    }

    public function test_demo_data_is_seeded_only_when_opted_in(): void
    {
        config(['setup.demo_seed' => true]);

        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas('companies', [
            'slug' => DemoDataSeeder::COMPANY_SLUG,
            'name' => DemoDataSeeder::COMPANY_NAME,
        ]);
        $this->assertDatabaseHas('users', [
            'email' => DemoDataSeeder::USERS['admin']['email'],
        ]);
    }
}
