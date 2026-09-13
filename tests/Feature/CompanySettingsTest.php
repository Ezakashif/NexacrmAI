<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanySettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
    }

    public function test_company_profile_and_settings_are_separate_pages(): void
    {
        $company = Company::factory()->create(['name' => 'Default Company']);
        $admin = User::factory()->admin()->create(['company_id' => $company->id]);

        $this->actingAs($admin)
            ->get(route('company.profile'))
            ->assertOk()
            ->assertSee('Company profile', false)
            ->assertSee('Edit settings', false)
            ->assertSee('Default Company', false)
            ->assertDontSee('name="name"', false);

        $this->actingAs($admin)
            ->get(route('company.settings.edit'))
            ->assertOk()
            ->assertSee('Company settings', false)
            ->assertSee('Address', false)
            ->assertSee('Regional defaults', false)
            ->assertSee('Business hours', false)
            ->assertSee('Company logo', false)
            ->assertSee('form-control', false)
            ->assertSee('name="name"', false)
            ->assertSee('name="timezone"', false)
            ->assertSee('Default Company', false)
            ->assertDontSee('mk-input', false)
            ->assertDontSee('mk-label', false);
    }

    public function test_company_admin_can_update_its_own_settings_and_activity_is_logged(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->create(['company_id' => $company->id]);

        $this->actingAs($admin)
            ->patch(route('company.settings.update'), [
                'name' => 'Acme CRM',
                'email' => 'hello@acme.test',
                'phone' => '+1 555 0100',
                'city' => 'Austin',
                'country' => 'US',
                'timezone' => 'America/Chicago',
                'currency' => 'USD',
                'business_hours' => ['monday' => '09:00–17:00'],
            ])
            ->assertRedirect(route('company.profile'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'name' => 'Acme CRM',
            'city' => 'Austin',
            'timezone' => 'America/Chicago',
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'company.settings_updated',
            'company_id' => $company->id,
            'user_id' => $admin->id,
        ]);
    }

    public function test_sales_user_cannot_access_company_profile_or_settings(): void
    {
        $company = Company::factory()->create();
        $sales = User::factory()->create(['company_id' => $company->id, 'role' => 'sales']);
        $sales->syncRolesFromLegacyColumn();

        $this->actingAs($sales)
            ->get(route('company.profile'))
            ->assertForbidden();

        $this->actingAs($sales)
            ->get(route('company.settings.edit'))
            ->assertForbidden();
    }

    public function test_super_admin_can_view_soft_deleted_company_profile(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $company = Company::factory()->create(['name' => 'Deleted Tenant']);

        $this->actingAs($superAdmin)
            ->delete(route('superadmin.companies.destroy', $company))
            ->assertRedirect();

        $this->actingAs($superAdmin)
            ->get(route('superadmin.companies.show', $company->id))
            ->assertOk()
            ->assertSee('Deleted Tenant', false)
            ->assertSee('soft-deleted', false)
            ->assertSee('Restore company', false);
    }
}
