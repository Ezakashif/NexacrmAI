<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Services\SuperAdmin\PlatformSettingsService;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformSettingsEnforcementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    public function test_registration_is_disabled_by_default(): void
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_enabled_registration_creates_company_and_admin(): void
    {
        app(PlatformSettingsService::class)->setMany([
            'registration_enabled' => true,
            'email_verification_required' => true,
        ]);
        app(PlatformSettingsService::class)->applyBranding();

        $this->get(route('register'))->assertOk();

        $this->post(route('register'), [
            'company_name' => 'Acme Labs',
            'name' => 'Acme Owner',
            'email' => 'owner@acme.test',
            'password' => 'SecurePass1!',
            'password_confirmation' => 'SecurePass1!',
        ])->assertRedirect(route('verification.notice'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('companies', ['name' => 'Acme Labs']);
        $this->assertDatabaseHas('users', [
            'email' => 'owner@acme.test',
            'is_super_admin' => 0,
        ]);

        $user = User::withoutCompanyScope()->where('email', 'owner@acme.test')->first();
        $this->assertNotNull($user?->company_id);
        $this->assertTrue($user->hasRole('admin'));
        $this->assertFalse($user->hasVerifiedEmail());
    }

    public function test_super_admin_can_toggle_registration_setting(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->put(route('superadmin.settings.update'), [
                'platform_name' => 'Algos CRM',
                'default_timezone' => 'UTC',
                'default_currency' => 'USD',
                'trial_duration_days' => 14,
                'default_company_status' => 'active',
                'registration_enabled' => '1',
                'email_verification_required' => '1',
                'maintenance_mode' => '0',
            ])
            ->assertRedirect();

        $this->assertSame('1', PlatformSetting::query()->where('key', 'registration_enabled')->value('value'));

        $this->post(route('logout'));
        $this->assertGuest();
        $this->get(route('register'))->assertOk();

        $this->actingAs($superAdmin)
            ->put(route('superadmin.settings.update'), [
                'platform_name' => 'Algos CRM',
                'default_timezone' => 'UTC',
                'default_currency' => 'USD',
                'trial_duration_days' => 14,
                'default_company_status' => 'active',
                'registration_enabled' => '0',
                'email_verification_required' => '1',
                'maintenance_mode' => '0',
            ])
            ->assertRedirect();

        $this->assertSame('0', PlatformSetting::query()->where('key', 'registration_enabled')->value('value'));

        $this->post(route('logout'));
        $this->assertGuest();
        $this->get('/register')->assertNotFound();
    }

    public function test_maintenance_mode_blocks_tenant_crm_but_allows_super_admin(): void
    {
        app(PlatformSettingsService::class)->setMany(['maintenance_mode' => true]);

        $tenant = User::factory()->admin()->create();
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($tenant)
            ->get(route('dashboard'))
            ->assertStatus(503)
            ->assertSee('under maintenance');

        $this->actingAs($superAdmin)
            ->get(route('superadmin.dashboard'))
            ->assertOk();

        $this->actingAs($superAdmin)
            ->get(route('superadmin.settings.edit'))
            ->assertOk();
    }

    public function test_tenant_login_is_rejected_during_maintenance(): void
    {
        app(PlatformSettingsService::class)->setMany(['maintenance_mode' => true]);

        $tenant = User::factory()->admin()->create([
            'email' => 'tenant@example.com',
        ]);
        $this->post('/login', [
            'email' => 'tenant@example.com',
            'password' => 'password',
        ])->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_resend_mailer_is_not_overridden_by_platform_smtp_settings(): void
    {
        config(['mail.default' => 'resend']);

        app(PlatformSettingsService::class)->setMany([
            'smtp_host' => 'smtp.example.com',
            'smtp_port' => 587,
        ]);
        app(PlatformSettingsService::class)->applyBranding();

        $this->assertSame('resend', config('mail.default'));
    }

    public function test_log_mailer_is_not_overridden_by_platform_smtp_settings(): void
    {
        config(['mail.default' => 'log']);

        app(PlatformSettingsService::class)->setMany([
            'smtp_host' => 'smtp.example.com',
            'smtp_port' => 587,
            'smtp_username' => 'mailer@example.com',
            'smtp_encryption' => 'tls',
        ]);
        app(PlatformSettingsService::class)->applyBranding();

        $this->assertSame('log', config('mail.default'));
    }

    public function test_platform_smtp_settings_apply_when_mailer_is_smtp(): void
    {
        config(['mail.default' => 'smtp']);

        app(PlatformSettingsService::class)->setMany([
            'smtp_host' => 'smtp.example.com',
            'smtp_port' => 587,
            'smtp_username' => 'mailer@example.com',
            'smtp_encryption' => 'tls',
        ]);
        app(PlatformSettingsService::class)->applyBranding();

        $this->assertSame('smtp', config('mail.default'));
        $this->assertSame('smtp.example.com', config('mail.mailers.smtp.host'));
        $this->assertSame(5, config('mail.mailers.smtp.timeout'));
    }

    public function test_super_admin_can_enable_and_disable_maintenance_mode(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $tenant = User::factory()->admin()->create();

        $this->actingAs($superAdmin)
            ->put(route('superadmin.settings.update'), [
                'platform_name' => 'Algos CRM',
                'default_timezone' => 'UTC',
                'default_currency' => 'USD',
                'trial_duration_days' => 14,
                'default_company_status' => 'active',
                'registration_enabled' => '0',
                'email_verification_required' => '1',
                'maintenance_mode' => '1',
            ])
            ->assertRedirect();

        $this->assertSame('1', PlatformSetting::query()->where('key', 'maintenance_mode')->value('value'));

        $this->actingAs($tenant)
            ->get(route('dashboard'))
            ->assertStatus(503);

        $this->actingAs($superAdmin)
            ->put(route('superadmin.settings.update'), [
                'platform_name' => 'Algos CRM',
                'default_timezone' => 'UTC',
                'default_currency' => 'USD',
                'trial_duration_days' => 14,
                'default_company_status' => 'active',
                'registration_enabled' => '0',
                'email_verification_required' => '1',
                'maintenance_mode' => '0',
            ])
            ->assertRedirect();

        $this->actingAs($tenant)
            ->get(route('dashboard'))
            ->assertOk();
    }
}
