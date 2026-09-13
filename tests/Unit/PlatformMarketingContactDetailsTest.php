<?php

namespace Tests\Unit;

use App\Models\PlatformSetting;
use App\Services\SuperAdmin\PlatformSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Unit coverage for every PlatformSettingsService marketing-contact overlay use case.
 */
class PlatformMarketingContactDetailsTest extends TestCase
{
    use RefreshDatabase;

    private PlatformSettingsService $settings;

    protected function setUp(): void
    {
        parent::setUp();

        $this->settings = app(PlatformSettingsService::class);

        // Isolate from seeded / env marketing defaults for deterministic assertions.
        config([
            'marketing.name' => 'Config Brand',
            'marketing.contact.email' => 'fallback@config.test',
            'marketing.contact.phone' => '+1 000 000 0000',
            'marketing.contact.address' => null,
            'marketing.social.linkedin' => 'https://linkedin.com/company/config',
            'marketing.social.facebook' => 'https://facebook.com/config',
            'marketing.social.twitter' => 'https://x.com/config',
            'marketing.social.github' => 'https://github.com/config',
            'app.name' => 'App Fallback',
        ]);
    }

    public function test_filled_company_address_is_applied_to_marketing_config(): void
    {
        $this->settings->setMany([
            'platform_name' => 'Northline',
            'company_address' => '500 Harbor Ave, Seattle, WA 98101',
        ]);

        $this->settings->applyBranding();

        $this->assertSame('500 Harbor Ave, Seattle, WA 98101', config('marketing.contact.address'));
    }

    public function test_empty_string_company_address_clears_marketing_address(): void
    {
        config(['marketing.contact.address' => '1200 Market Street, Suite 400, San Francisco, CA 94103']);

        $this->settings->setMany([
            'platform_name' => 'Northline',
            'company_address' => '',
        ]);

        $this->settings->applyBranding();

        $this->assertNull(config('marketing.contact.address'));
    }

    public function test_null_company_address_clears_marketing_address(): void
    {
        config(['marketing.contact.address' => 'Legacy Config Address']);

        $this->settings->setMany([
            'platform_name' => 'Northline',
            'company_address' => null,
        ]);

        $this->settings->applyBranding();

        $this->assertNull(config('marketing.contact.address'));
        $this->assertArrayHasKey('company_address', $this->settings->all());
    }

    public function test_whitespace_only_company_address_is_treated_as_empty(): void
    {
        config(['marketing.contact.address' => 'Should Not Appear']);

        $this->settings->setMany([
            'platform_name' => 'Northline',
            'company_address' => "   \t\n  ",
        ]);

        $this->settings->applyBranding();

        $this->assertNull(config('marketing.contact.address'));
    }

    public function test_unset_company_address_falls_back_to_filled_marketing_config(): void
    {
        config(['marketing.contact.address' => 'Env Config Address Only']);

        $this->settings->setMany([
            'platform_name' => 'Northline',
            'company_email' => 'ops@northline.test',
        ]);

        $this->assertArrayNotHasKey('company_address', $this->settings->all());

        $this->settings->applyBranding();

        $this->assertSame('Env Config Address Only', config('marketing.contact.address'));
    }

    public function test_unset_company_address_stays_null_when_marketing_config_address_empty(): void
    {
        config(['marketing.contact.address' => null]);

        $this->settings->setMany(['platform_name' => 'Northline']);
        $this->settings->applyBranding();

        $this->assertNull(config('marketing.contact.address'));
    }

    public function test_clearing_previously_set_address_removes_it_from_config(): void
    {
        $this->settings->setMany([
            'platform_name' => 'Northline',
            'company_address' => 'First Address',
        ]);
        $this->settings->applyBranding();
        $this->assertSame('First Address', config('marketing.contact.address'));

        $this->settings->setMany(['company_address' => '']);
        $this->settings->applyBranding();

        $this->assertNull(config('marketing.contact.address'));
    }

    public function test_updating_company_address_replaces_previous_value(): void
    {
        $this->settings->setMany([
            'platform_name' => 'Northline',
            'company_address' => 'Old Address',
        ]);
        $this->settings->applyBranding();

        $this->settings->setMany(['company_address' => 'New Address 99']);
        $this->settings->applyBranding();

        $this->assertSame('New Address 99', config('marketing.contact.address'));
        $this->assertNotSame('Old Address', config('marketing.contact.address'));
    }

    public function test_filled_company_email_overrides_marketing_config(): void
    {
        $this->settings->setMany([
            'platform_name' => 'Northline',
            'company_email' => 'hello@northline.test',
        ]);
        $this->settings->applyBranding();

        $this->assertSame('hello@northline.test', config('marketing.contact.email'));
    }

    public function test_empty_company_email_falls_back_to_marketing_config(): void
    {
        $this->settings->setMany([
            'platform_name' => 'Northline',
            'company_email' => '',
        ]);
        $this->settings->applyBranding();

        $this->assertSame('fallback@config.test', config('marketing.contact.email'));
    }

    public function test_filled_company_phone_overrides_marketing_config(): void
    {
        $this->settings->setMany([
            'platform_name' => 'Northline',
            'company_phone' => '+1 555 999 1212',
        ]);
        $this->settings->applyBranding();

        $this->assertSame('+1 555 999 1212', config('marketing.contact.phone'));
    }

    public function test_empty_company_phone_falls_back_to_marketing_config(): void
    {
        $this->settings->setMany([
            'platform_name' => 'Northline',
            'company_phone' => '',
        ]);
        $this->settings->applyBranding();

        $this->assertSame('+1 000 000 0000', config('marketing.contact.phone'));
    }

    public function test_filled_social_urls_override_marketing_config(): void
    {
        $this->settings->setMany([
            'platform_name' => 'Northline',
            'company_linkedin_url' => 'https://linkedin.com/company/northline',
            'company_facebook_url' => 'https://facebook.com/northline',
            'company_twitter_url' => 'https://x.com/northline',
            'company_github_url' => 'https://github.com/northline',
        ]);
        $this->settings->applyBranding();

        $this->assertSame('https://linkedin.com/company/northline', config('marketing.social.linkedin'));
        $this->assertSame('https://facebook.com/northline', config('marketing.social.facebook'));
        $this->assertSame('https://x.com/northline', config('marketing.social.twitter'));
        $this->assertSame('https://github.com/northline', config('marketing.social.github'));
    }

    public function test_empty_social_urls_fall_back_to_marketing_config(): void
    {
        $this->settings->setMany([
            'platform_name' => 'Northline',
            'company_linkedin_url' => '',
            'company_facebook_url' => '',
            'company_twitter_url' => '',
            'company_github_url' => '',
        ]);
        $this->settings->applyBranding();

        $this->assertSame('https://linkedin.com/company/config', config('marketing.social.linkedin'));
        $this->assertSame('https://facebook.com/config', config('marketing.social.facebook'));
        $this->assertSame('https://x.com/config', config('marketing.social.twitter'));
        $this->assertSame('https://github.com/config', config('marketing.social.github'));
    }

    public function test_platform_name_is_applied_to_marketing_brand(): void
    {
        $this->settings->setMany(['platform_name' => 'Northline CRM']);
        $this->settings->applyBranding();

        $this->assertSame('Northline CRM', config('marketing.name'));
        $this->assertSame('Northline CRM', config('app.name'));
    }

    public function test_missing_platform_name_falls_back_to_app_name(): void
    {
        PlatformSetting::query()->where('key', 'platform_name')->delete();
        Cache::forget(PlatformSettingsService::CACHE_KEY);
        config(['app.name' => 'App Fallback']);

        $this->settings->applyBranding();

        $this->assertSame('App Fallback', config('marketing.name'));
    }

    public function test_all_company_contact_fields_apply_together(): void
    {
        $this->settings->setMany([
            'platform_name' => 'Acme Suite',
            'company_email' => 'hello@acme.test',
            'company_phone' => '+1 111 222 3333',
            'company_address' => '1 Infinite Loop',
            'company_linkedin_url' => 'https://linkedin.com/company/acme',
            'company_facebook_url' => 'https://facebook.com/acme',
            'company_twitter_url' => 'https://x.com/acme',
            'company_github_url' => 'https://github.com/acme',
        ]);
        $this->settings->applyBranding();

        $this->assertSame('Acme Suite', config('marketing.name'));
        $this->assertSame('hello@acme.test', config('marketing.contact.email'));
        $this->assertSame('+1 111 222 3333', config('marketing.contact.phone'));
        $this->assertSame('1 Infinite Loop', config('marketing.contact.address'));
        $this->assertSame('https://linkedin.com/company/acme', config('marketing.social.linkedin'));
        $this->assertSame('https://facebook.com/acme', config('marketing.social.facebook'));
        $this->assertSame('https://x.com/acme', config('marketing.social.twitter'));
        $this->assertSame('https://github.com/acme', config('marketing.social.github'));
    }

    public function test_empty_address_does_not_fall_back_to_config_placeholder(): void
    {
        config(['marketing.contact.address' => '1200 Market Street, Suite 400, San Francisco, CA 94103']);

        $this->settings->setMany([
            'platform_name' => 'Northline',
            'company_email' => 'hello@northline.test',
            'company_phone' => '+1 555 010 2000',
            'company_address' => '',
        ]);
        $this->settings->applyBranding();

        $this->assertSame('hello@northline.test', config('marketing.contact.email'));
        $this->assertSame('+1 555 010 2000', config('marketing.contact.phone'));
        $this->assertNull(config('marketing.contact.address'));
    }
}
