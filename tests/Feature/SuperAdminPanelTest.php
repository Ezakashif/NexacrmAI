<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    public function test_super_admin_can_view_platform_dashboard(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->get(route('superadmin.dashboard'))
            ->assertOk()
            ->assertSee('Platform overview');
    }

    public function test_tenant_admin_cannot_access_superadmin(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('superadmin.dashboard'))
            ->assertForbidden();
    }

    public function test_super_admin_login_redirects_to_platform_dashboard(): void
    {
        $superAdmin = User::factory()->superAdmin()->create([
            'email' => 'platform@example.com',
        ]);

        $this->post('/login', [
            'email' => 'platform@example.com',
            'password' => 'password',
        ])->assertRedirect(route('superadmin.dashboard'));
    }

    public function test_super_admin_hitting_crm_is_redirected_to_platform(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->get(route('dashboard'))
            ->assertRedirect(route('superadmin.dashboard'));
    }

    public function test_super_admin_can_provision_company_with_admin(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $response = $this->actingAs($superAdmin)->post(route('superadmin.companies.store'), [
            'name' => 'Acme CRM',
            'slug' => 'acme',
            'status' => 'active',
            'admin_name' => 'Acme Admin',
            'admin_email' => 'admin@acme.test',
            'admin_password' => 'SecurePass1!',
        ]);

        $company = Company::query()->where('slug', 'acme')->first();

        $this->assertNotNull($company);
        $response->assertRedirect(route('superadmin.companies.show', $company));

        $admin = User::withoutCompanyScope()->where('email', 'admin@acme.test')->first();

        $this->assertNotNull($admin);
        $this->assertSame($company->id, $admin->company_id);
        $this->assertTrue($admin->hasRole('admin'));
        $this->assertFalse($admin->isSuperAdmin());
    }

    public function test_company_admin_password_must_be_strong(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->from(route('superadmin.companies.create'))
            ->post(route('superadmin.companies.store'), [
                'name' => 'Weak Pass Co',
                'slug' => 'weak-pass',
                'status' => 'active',
                'admin_name' => 'Weak Admin',
                'admin_email' => 'weak@acme.test',
                'admin_password' => 'password',
            ])
            ->assertRedirect(route('superadmin.companies.create'))
            ->assertSessionHasErrors('admin_password');

        $this->assertNull(Company::query()->where('slug', 'weak-pass')->first());
    }

    public function test_super_admin_can_suspend_company(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $company = Company::factory()->create(['slug' => 'suspendable']);

        $this->actingAs($superAdmin)
            ->patch(route('superadmin.companies.status', $company), [
                'status' => 'suspended',
            ])
            ->assertRedirect();

        $this->assertSame('suspended', $company->fresh()->status);
    }

    public function test_tenant_user_from_suspended_company_cannot_use_crm(): void
    {
        $company = Company::default();
        $company->update(['status' => 'suspended']);

        $user = User::factory()->admin()->create(['company_id' => $company->id]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
