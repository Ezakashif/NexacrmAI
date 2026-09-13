<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\ImpersonationLog;
use App\Models\Lead;
use App\Models\Plan;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Services\SuperAdmin\ImpersonationService;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SuperAdminDashboardUpgradeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    public function test_dashboard_shows_live_platform_stats_and_widgets(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        Company::factory()->count(2)->create();
        Company::factory()->suspended()->create();
        Company::factory()->onTrial()->create();
        Company::factory()->expired()->create();

        $this->actingAs($superAdmin)
            ->get(route('superadmin.dashboard'))
            ->assertOk()
            ->assertSee('Total Companies')
            ->assertSee('Trial Companies')
            ->assertSee('Platform growth')
            ->assertSee('System health')
            ->assertSee('Platform alerts')
            ->assertSee('Recent activity')
            ->assertSee('Quick actions');
    }

    public function test_analytics_endpoints_return_monthly_series(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $company = Company::factory()->create();

        Lead::factory()->count(2)->create(['company_id' => $company->id]);
        Customer::factory()->create(['company_id' => $company->id]);

        $this->actingAs($superAdmin)
            ->getJson(route('superadmin.analytics.companies'))
            ->assertOk()
            ->assertJsonStructure(['labels', 'values']);

        $this->actingAs($superAdmin)
            ->getJson(route('superadmin.analytics.leads'))
            ->assertOk()
            ->assertJsonStructure(['labels', 'values']);

        $this->actingAs($superAdmin)
            ->getJson(route('superadmin.analytics.customers'))
            ->assertOk()
            ->assertJsonStructure(['labels', 'values']);
    }

    public function test_companies_index_shows_enriched_columns(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $plan = Plan::query()->where('slug', 'starter')->firstOrFail();
        $company = Company::factory()->create([
            'name' => 'Nova Labs',
            'plan_id' => $plan->id,
        ]);
        $owner = User::factory()->admin()->create([
            'company_id' => $company->id,
            'name' => 'Nova Owner',
            'email' => 'owner@nova.test',
        ]);
        $company->update(['owner_id' => $owner->id]);

        $this->actingAs($superAdmin)
            ->get(route('superadmin.companies.index'))
            ->assertOk()
            ->assertSee('Nova Labs')
            ->assertSee('Nova Owner')
            ->assertSee('owner@nova.test')
            ->assertSee('Starter')
            ->assertSee('Login as');
    }

    public function test_company_profile_page_shows_usage_and_users(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $company = Company::factory()->create(['name' => 'Profile Co']);
        User::factory()->admin()->create([
            'company_id' => $company->id,
            'name' => 'Profile Admin',
            'email' => 'admin@profile.test',
        ]);

        $this->actingAs($superAdmin)
            ->get(route('superadmin.companies.show', $company))
            ->assertOk()
            ->assertSee('Profile Co')
            ->assertSee('Usage overview')
            ->assertSee('Profile Admin')
            ->assertSee('Login As Admin');
    }

    public function test_super_admin_can_impersonate_and_return(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->create([
            'company_id' => $company->id,
            'email' => 'tenant-admin@example.com',
        ]);

        $this->actingAs($superAdmin)
            ->post(route('superadmin.companies.impersonate', $company))
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($admin);
        $this->assertTrue(session()->has(ImpersonationService::SESSION_IMPERSONATOR_ID));
        $this->assertDatabaseHas('impersonation_logs', [
            'super_admin_id' => $superAdmin->id,
            'target_user_id' => $admin->id,
            'company_id' => $company->id,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'impersonation.started',
            'user_id' => $superAdmin->id,
        ]);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Impersonation active')
            ->assertSee('Return to Super Admin');

        $this->post(route('impersonation.leave'))
            ->assertRedirect(route('superadmin.dashboard'));

        $this->assertAuthenticatedAs($superAdmin);
        $this->assertFalse(session()->has(ImpersonationService::SESSION_IMPERSONATOR_ID));
        $this->assertNotNull(ImpersonationLog::query()->where('target_user_id', $admin->id)->value('ended_at'));
    }

    public function test_impersonation_requires_active_company_admin(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $company = Company::factory()->create();

        $this->actingAs($superAdmin)
            ->from(route('superadmin.companies.show', $company))
            ->post(route('superadmin.companies.impersonate', $company))
            ->assertRedirect(route('superadmin.companies.show', $company))
            ->assertSessionHasErrors('impersonation');
    }

    public function test_super_admin_can_update_platform_settings(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->put(route('superadmin.settings.update'), [
                'platform_name' => 'Acme Platform',
                'default_timezone' => 'UTC',
                'default_currency' => 'USD',
                'mail_from_name' => 'Acme',
                'mail_from_address' => 'hello@acme.test',
                'trial_duration_days' => 21,
                'default_company_status' => 'active',
                'registration_enabled' => '1',
                'maintenance_mode' => '0',
            ])
            ->assertRedirect();

        $this->assertSame('Acme Platform', PlatformSetting::query()->where('key', 'platform_name')->value('value'));
        $this->assertSame('21', PlatformSetting::query()->where('key', 'trial_duration_days')->value('value'));
    }

    public function test_updating_trial_duration_syncs_trial_plans(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $free = Plan::factory()->create(['slug' => 'free-trial', 'is_free' => true, 'trial_days' => 7]);
        $paidTrial = Plan::factory()->create(['slug' => 'pro-trial', 'is_free' => false, 'trial_days' => 14]);
        $enterprise = Plan::factory()->create(['slug' => 'no-trial', 'is_free' => false, 'trial_days' => 0]);

        $this->actingAs($superAdmin)
            ->put(route('superadmin.settings.update'), [
                'platform_name' => 'Algos CRM',
                'default_timezone' => 'UTC',
                'default_currency' => 'USD',
                'trial_duration_days' => 30,
                'default_company_status' => 'active',
                'registration_enabled' => '1',
                'maintenance_mode' => '0',
            ])
            ->assertRedirect();

        $this->assertSame(30, $free->fresh()->trial_days);
        $this->assertSame(30, $paidTrial->fresh()->trial_days);
        $this->assertSame(0, $enterprise->fresh()->trial_days);
    }

    public function test_platform_logo_is_applied_to_login_and_crm_branding(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        Storage::fake('public');

        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
        $tmp = tempnam(sys_get_temp_dir(), 'logo');
        file_put_contents($tmp, $png);
        $logo = new UploadedFile($tmp, 'brand.png', 'image/png', null, true);

        $this->actingAs($superAdmin)
            ->put(route('superadmin.settings.update'), [
                'platform_name' => 'Brand CRM',
                'default_timezone' => 'UTC',
                'default_currency' => 'USD',
                'trial_duration_days' => 14,
                'default_company_status' => 'active',
                'platform_logo' => $logo,
            ])
            ->assertRedirect();

        $storedPath = PlatformSetting::query()->where('key', 'platform_logo_path')->value('value');
        $this->assertNotEmpty($storedPath);
        $this->assertStringEndsWith('.png', $storedPath);
        Storage::disk('public')->assertExists($storedPath);

        app(\App\Services\SuperAdmin\PlatformSettingsService::class)->applyBranding();
        $this->assertSame(
            'storage/'.PlatformSetting::query()->where('key', 'platform_logo_path')->value('value'),
            config('adminlte.auth_logo.img.path')
        );
        $this->assertNotEmpty(config('adminlte.logo_img'));
        $this->assertSame('Brand CRM', config('app.name'));

        auth()->logout();

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Brand CRM', false);
    }

    public function test_platform_logo_processor_removes_black_background(): void
    {
        $source = base_path('public/branding/algos-logo.png');
        $this->assertFileExists($source);

        $output = sys_get_temp_dir().'/processed-logo-test.png';
        app(\App\Services\SuperAdmin\PlatformLogoProcessor::class)
            ->processToTransparentPng($source, $output);

        $this->assertFileExists($output);
        $this->assertGreaterThan(1000, filesize($output));
        @unlink($output);
    }

    public function test_dark_sidebar_uses_light_logo_variant(): void
    {
        $settings = app(\App\Services\SuperAdmin\PlatformSettingsService::class);
        $settings->setMany([
            'platform_name' => 'Algos CRM',
            'platform_logo_path' => 'platform/dark-logo.png',
            'platform_logo_light_path' => 'platform/light-logo.png',
        ]);

        Storage::disk('public')->put('platform/dark-logo.png', 'dark');
        Storage::disk('public')->put('platform/light-logo.png', 'light');

        $settings->applyBranding();

        $this->assertSame('storage/platform/light-logo.png', config('adminlte.logo_img'));
        $this->assertSame('storage/platform/dark-logo.png', config('adminlte.auth_logo.img.path'));
    }

    public function test_super_admin_can_create_another_super_admin(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->post(route('superadmin.super-admins.store'), [
                'name' => 'Second Admin',
                'email' => 'second@platform.test',
                'password' => 'SecurePass1!',
                'password_confirmation' => 'SecurePass1!',
            ])
            ->assertRedirect(route('superadmin.super-admins.index'));

        $created = User::withoutCompanyScope()->where('email', 'second@platform.test')->first();
        $this->assertNotNull($created);
        $this->assertTrue($created->is_super_admin);
        $this->assertNull($created->company_id);
    }

    public function test_global_search_finds_companies_and_users(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $company = Company::factory()->create([
            'name' => 'Searchable Agency',
            'slug' => 'searchable-agency',
            'email' => 'hello@searchable.test',
        ]);
        User::factory()->create([
            'company_id' => $company->id,
            'name' => 'Search User',
            'email' => 'findme@searchable.test',
        ]);

        $this->actingAs($superAdmin)
            ->get(route('superadmin.search.index', ['q' => 'Searchable']))
            ->assertOk()
            ->assertSee('Searchable Agency')
            ->assertSee('findme@searchable.test');

        $this->actingAs($superAdmin)
            ->getJson(route('superadmin.search.suggest', ['q' => 'findme']))
            ->assertOk()
            ->assertJsonFragment(['email' => 'findme@searchable.test']);
    }

    public function test_super_admin_can_soft_delete_company(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $company = Company::factory()->create(['name' => 'Delete Me', 'slug' => 'delete-me']);

        $this->actingAs($superAdmin)
            ->delete(route('superadmin.companies.destroy', $company))
            ->assertRedirect(route('superadmin.companies.index'));

        $this->assertSoftDeleted($company);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'company.deleted',
        ]);
    }

    public function test_tenant_admin_cannot_access_new_platform_routes(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('superadmin.settings.edit'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->getJson(route('superadmin.analytics.companies'))
            ->assertForbidden();
    }
}
