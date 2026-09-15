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

    public function test_guest_is_redirected_to_login_from_superadmin(): void
    {
        $this->get(route('superadmin.dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_super_admin_can_view_platform_dashboard(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->get(route('superadmin.dashboard'))
            ->assertOk()
            ->assertSee('Platform overview')
            ->assertDontSee('algos.', false);
    }

    public function test_tenant_admin_cannot_access_superadmin(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('superadmin.dashboard'))
            ->assertForbidden()
            ->assertSee('Access denied', false)
            ->assertSee('NexaCRM', false)
            ->assertSee('Super Admin access required.', false);
    }

    public function test_companies_index_labels_default_company_as_platform_shell(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->get(route('superadmin.companies.index'))
            ->assertOk()
            ->assertSee('Default Company', false)
            ->assertSee('Platform shell', false)
            ->assertSee('not a tenant workspace', false);
    }

    public function test_company_create_form_does_not_promise_auto_generated_admin_password(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->get(route('superadmin.companies.create'))
            ->assertOk()
            ->assertSee('Required when you enter an admin email', false)
            ->assertDontSee('Leave blank to auto-generate', false);
    }

    public function test_company_admin_password_is_required_with_admin_email(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->from(route('superadmin.companies.create'))
            ->post(route('superadmin.companies.store'), [
                'name' => 'Blank Pass Co',
                'slug' => 'blank-pass',
                'status' => 'active',
                'admin_name' => 'Blank Admin',
                'admin_email' => 'blank@acme.test',
                'admin_password' => '',
            ])
            ->assertRedirect(route('superadmin.companies.create'))
            ->assertSessionHasErrors('admin_password');

        $this->assertNull(Company::query()->where('slug', 'blank-pass')->first());
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
