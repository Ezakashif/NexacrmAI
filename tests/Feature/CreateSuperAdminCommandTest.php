<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Permission;
use App\Models\Scopes\CompanyScope;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateSuperAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_creates_the_first_super_admin(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->artisan('nexacrm:create-super-admin', [
            '--name' => 'Install Admin',
            '--email' => 'install@nexacrm.test',
            '--password' => 'NexaSetup#9x',
        ])->assertSuccessful();

        $user = User::withoutGlobalScope(CompanyScope::class)
            ->where('email', 'install@nexacrm.test')
            ->first();

        $this->assertNotNull($user);
        $this->assertTrue($user->isSuperAdmin());
        $this->assertNull($user->company_id);
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertSame('active', $user->status);
        $this->assertTrue(Hash::check('NexaSetup#9x', $user->password));
    }

    public function test_created_super_admin_can_sign_in_and_provision_a_company(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->artisan('nexacrm:create-super-admin', [
            '--email' => 'install@nexacrm.test',
            '--password' => 'NexaSetup#9x',
        ])->assertSuccessful();

        $this->post('/login', [
            'email' => 'install@nexacrm.test',
            'password' => 'NexaSetup#9x',
        ])->assertRedirect(route('superadmin.dashboard'));

        $this->assertAuthenticated();

        $this->post(route('superadmin.companies.store'), [
            'name' => 'Buyer Workspace',
            'slug' => 'buyer-workspace',
            'status' => 'active',
            'admin_name' => 'Workspace Admin',
            'admin_email' => 'admin@buyer.test',
            'admin_password' => 'TenantPass#9x',
        ]);

        $company = Company::query()->where('slug', 'buyer-workspace')->first();
        $this->assertNotNull($company);

        $admin = User::withoutGlobalScope(CompanyScope::class)
            ->where('email', 'admin@buyer.test')
            ->first();

        $this->assertNotNull($admin);
        $this->assertSame($company->id, $admin->company_id);
        $this->assertTrue($admin->hasRole('admin'));
        $this->assertTrue($admin->hasPermission('view.leads'));
        $this->assertGreaterThan(0, Permission::query()->count());

        $this->post('/logout');

        $this->post('/login', [
            'email' => 'admin@buyer.test',
            'password' => 'TenantPass#9x',
        ])->assertRedirect(route('dashboard'));
    }

    public function test_command_does_not_print_the_password(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->artisan('nexacrm:create-super-admin', [
            '--email' => 'install@nexacrm.test',
            '--password' => 'NexaSetup#9x',
        ])->expectsOutputToContain('install@nexacrm.test')
            ->doesntExpectOutputToContain('NexaSetup#9x')
            ->assertSuccessful();
    }

    public function test_command_refuses_a_second_super_admin(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->artisan('nexacrm:create-super-admin', [
            '--email' => 'first@nexacrm.test',
            '--password' => 'NexaSetup#9x',
        ])->assertSuccessful();

        $this->artisan('nexacrm:create-super-admin', [
            '--email' => 'second@nexacrm.test',
            '--password' => 'NexaSetup#9x',
        ])->assertFailed();

        $this->assertSame(1, User::withoutGlobalScope(CompanyScope::class)->where('is_super_admin', true)->count());
        $this->assertDatabaseMissing('users', ['email' => 'second@nexacrm.test']);
    }

    public function test_command_rejects_a_weak_password(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->artisan('nexacrm:create-super-admin', [
            '--email' => 'install@nexacrm.test',
            '--password' => 'password',
        ])->assertFailed();

        $this->assertSame(0, User::withoutGlobalScope(CompanyScope::class)->count());
    }

    public function test_non_interactive_command_requires_email_and_password(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->artisan('nexacrm:create-super-admin')
            ->assertFailed();

        $this->assertSame(0, User::withoutGlobalScope(CompanyScope::class)->count());
    }
}
