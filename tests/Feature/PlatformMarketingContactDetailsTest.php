<?php

namespace Tests\Feature;

use App\Mail\Marketing\ContactInquiryMail;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Services\SuperAdmin\PlatformSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Feature coverage for every Super Admin company-details → public UI use case.
 */
class PlatformMarketingContactDetailsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validSettingsPayload(array $overrides = []): array
    {
        return array_merge([
            'platform_name' => 'Northline CRM',
            'default_timezone' => 'UTC',
            'default_currency' => 'USD',
            'trial_duration_days' => 14,
            'default_company_status' => 'active',
        ], $overrides);
    }

    private function saveCompanySettings(array $overrides = []): User
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->put(route('superadmin.settings.update'), $this->validSettingsPayload($overrides))
            ->assertRedirect();

        return $superAdmin;
    }

    public function test_footer_and_contact_page_reflect_super_admin_company_settings(): void
    {
        $this->saveCompanySettings([
            'company_email' => 'hello@northline.example',
            'company_phone' => '+1 555 999 1212',
            'company_address' => '500 Harbor Ave, Seattle, WA 98101',
            'company_linkedin_url' => 'https://www.linkedin.com/company/northline',
            'company_facebook_url' => 'https://www.facebook.com/northline',
            'company_twitter_url' => 'https://x.com/northline',
            'company_github_url' => 'https://github.com/northline',
        ]);

        app(PlatformSettingsService::class)->applyBranding();

        $this->assertSame('hello@northline.example', config('marketing.contact.email'));
        $this->assertSame('+1 555 999 1212', config('marketing.contact.phone'));
        $this->assertSame('500 Harbor Ave, Seattle, WA 98101', config('marketing.contact.address'));
        $this->assertSame('Northline CRM', config('marketing.name'));

        auth()->logout();

        $this->get(route('marketing.contact'))
            ->assertOk()
            ->assertSee('hello@northline.example', false)
            ->assertSee('+1 555 999 1212', false)
            ->assertSee('500 Harbor Ave, Seattle, WA 98101', false)
            ->assertSee('https://www.linkedin.com/company/northline', false)
            ->assertSee('https://www.facebook.com/northline', false)
            ->assertSee('https://x.com/northline', false)
            ->assertSee('https://github.com/northline', false)
            ->assertDontSee('hello@algos.test', false)
            ->assertDontSee('1200 Market Street', false);
    }

    public function test_contact_page_shows_address_label_when_address_is_set(): void
    {
        $this->saveCompanySettings([
            'company_email' => 'hello@northline.example',
            'company_phone' => '+1 555 999 1212',
            'company_address' => '500 Harbor Ave, Seattle, WA 98101',
        ]);
        auth()->logout();

        $html = $this->get(route('marketing.contact'))->assertOk()->getContent();

        $this->assertStringContainsString('>Address</', $html);
        $this->assertStringContainsString('500 Harbor Ave, Seattle, WA 98101', $html);
    }

    public function test_empty_company_address_is_hidden_from_contact_page(): void
    {
        $this->saveCompanySettings([
            'company_email' => 'hello@northline.example',
            'company_phone' => '+1 555 999 1212',
            'company_address' => '',
        ]);

        app(PlatformSettingsService::class)->applyBranding();
        $this->assertNull(config('marketing.contact.address'));

        auth()->logout();

        $html = $this->get(route('marketing.contact'))
            ->assertOk()
            ->assertSee('hello@northline.example', false)
            ->assertSee('+1 555 999 1212', false)
            ->assertDontSee('1200 Market Street', false)
            ->assertDontSee('San Francisco', false)
            ->assertDontSee('PostalAddress', false)
            ->assertDontSee('streetAddress', false)
            ->assertDontSee('Google Maps', false)
            ->getContent();

        $this->assertStringNotContainsString('>Address</', $html);
    }

    public function test_footer_shows_address_when_set(): void
    {
        $this->saveCompanySettings([
            'company_email' => 'hello@northline.example',
            'company_phone' => '+1 555 999 1212',
            'company_address' => '500 Harbor Ave, Seattle, WA 98101',
        ]);
        auth()->logout();

        $this->get(route('marketing.home'))
            ->assertOk()
            ->assertSee('500 Harbor Ave, Seattle, WA 98101', false)
            ->assertSee('hello@northline.example', false)
            ->assertSee('+1 555 999 1212', false);
    }

    public function test_footer_hides_address_when_empty_on_all_marketing_pages(): void
    {
        $this->saveCompanySettings([
            'company_email' => 'hello@northline.example',
            'company_phone' => '+1 555 999 1212',
            'company_address' => '',
        ]);
        auth()->logout();

        foreach ([
            'marketing.home',
            'marketing.features',
            'marketing.pricing',
            'marketing.documentation',
            'marketing.about',
            'marketing.contact',
        ] as $route) {
            $html = $this->get(route($route))->assertOk()->getContent();

            $this->assertStringNotContainsString('1200 Market Street', $html, "Unexpected address on {$route}");
            $this->assertStringNotContainsString('San Francisco, CA 94103', $html, "Unexpected address on {$route}");
            $this->assertStringContainsString('hello@northline.example', $html);
        }
    }

    public function test_json_ld_includes_postal_address_when_address_is_set(): void
    {
        $this->saveCompanySettings([
            'company_email' => 'hello@northline.example',
            'company_address' => '500 Harbor Ave, Seattle, WA 98101',
        ]);
        auth()->logout();

        $this->get(route('marketing.contact'))
            ->assertOk()
            ->assertSee('PostalAddress', false)
            ->assertSee('streetAddress', false)
            ->assertSee('500 Harbor Ave, Seattle, WA 98101', false);
    }

    public function test_json_ld_omits_address_when_company_address_is_empty(): void
    {
        $this->saveCompanySettings([
            'company_email' => 'hello@northline.example',
            'company_address' => '',
        ]);
        auth()->logout();

        $this->get(route('marketing.home'))
            ->assertOk()
            ->assertDontSee('PostalAddress', false)
            ->assertDontSee('streetAddress', false);
    }

    public function test_map_placeholder_renders_only_when_address_is_present(): void
    {
        config(['marketing.contact.address' => '500 Harbor Ave, Seattle, WA 98101']);

        $withAddress = (string) $this->blade('<x-marketing.map-placeholder />');
        $this->assertStringContainsString('500 Harbor Ave, Seattle, WA 98101', $withAddress);
        $this->assertStringContainsString('role="img"', $withAddress);

        config(['marketing.contact.address' => null]);

        $withoutAddress = (string) $this->blade('<x-marketing.map-placeholder />');
        $this->assertSame('', trim($withoutAddress));
    }

    public function test_map_placeholder_uses_explicit_prop_over_config(): void
    {
        config(['marketing.contact.address' => 'Config Address']);

        $html = (string) $this->blade('<x-marketing.map-placeholder address="Prop Address" />');

        $this->assertStringContainsString('Prop Address', $html);
        $this->assertStringNotContainsString('Config Address', $html);
    }

    public function test_map_placeholder_hides_when_config_and_prop_are_empty(): void
    {
        config(['marketing.contact.address' => '']);

        $html = (string) $this->blade('<x-marketing.map-placeholder address="" />');

        $this->assertSame('', trim($html));
    }

    public function test_settings_form_persists_and_redisplays_company_address(): void
    {
        $superAdmin = $this->saveCompanySettings([
            'company_address' => '500 Harbor Ave, Seattle, WA 98101',
        ]);

        $this->actingAs($superAdmin)
            ->get(route('superadmin.settings.edit'))
            ->assertOk()
            ->assertSee('name="company_address"', false)
            ->assertSee('500 Harbor Ave, Seattle, WA 98101', false)
            ->assertSee('(optional)', false);
    }

    public function test_settings_form_does_not_prefill_placeholder_address_when_empty(): void
    {
        $superAdmin = $this->saveCompanySettings([
            'company_address' => '',
        ]);

        $html = $this->actingAs($superAdmin)
            ->get(route('superadmin.settings.edit'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('1200 Market Street', $html);
        $this->assertMatchesRegularExpression(
            '/name="company_address"[^>]*value=""/',
            $html
        );
    }

    public function test_company_address_max_length_is_accepted(): void
    {
        $address = str_repeat('A', 500);

        $this->saveCompanySettings(['company_address' => $address]);

        $this->assertSame($address, PlatformSetting::query()->where('key', 'company_address')->value('value'));
    }

    public function test_company_address_longer_than_max_is_rejected(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->from(route('superadmin.settings.edit'))
            ->put(route('superadmin.settings.update'), $this->validSettingsPayload([
                'company_address' => str_repeat('A', 501),
            ]))
            ->assertRedirect(route('superadmin.settings.edit'))
            ->assertSessionHasErrors('company_address');
    }

    public function test_invalid_company_email_is_rejected(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->from(route('superadmin.settings.edit'))
            ->put(route('superadmin.settings.update'), $this->validSettingsPayload([
                'company_email' => 'not-an-email',
            ]))
            ->assertRedirect(route('superadmin.settings.edit'))
            ->assertSessionHasErrors('company_email');
    }

    public function test_invalid_social_urls_are_rejected(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->from(route('superadmin.settings.edit'))
            ->put(route('superadmin.settings.update'), $this->validSettingsPayload([
                'company_linkedin_url' => 'not-a-url',
                'company_facebook_url' => 'also-bad',
                'company_twitter_url' => 'nope',
                'company_github_url' => 'bad',
            ]))
            ->assertRedirect(route('superadmin.settings.edit'))
            ->assertSessionHasErrors([
                'company_linkedin_url',
                'company_facebook_url',
                'company_twitter_url',
                'company_github_url',
            ]);
    }

    public function test_omitted_optional_company_fields_are_accepted(): void
    {
        $this->saveCompanySettings([
            // intentionally omit email/phone/address/social
        ]);

        $this->assertNull(PlatformSetting::query()->where('key', 'company_address')->value('value'));
        $this->assertNull(PlatformSetting::query()->where('key', 'company_email')->value('value'));
    }

    public function test_clearing_address_after_it_was_set_removes_it_from_public_ui(): void
    {
        $superAdmin = $this->saveCompanySettings([
            'company_email' => 'hello@northline.example',
            'company_address' => '500 Harbor Ave, Seattle, WA 98101',
        ]);
        auth()->logout();

        $this->get(route('marketing.contact'))
            ->assertOk()
            ->assertSee('500 Harbor Ave, Seattle, WA 98101', false);

        $this->actingAs($superAdmin)
            ->put(route('superadmin.settings.update'), $this->validSettingsPayload([
                'company_email' => 'hello@northline.example',
                'company_address' => '',
            ]))
            ->assertRedirect();
        auth()->logout();

        $html = $this->get(route('marketing.contact'))->assertOk()->getContent();
        $this->assertStringNotContainsString('500 Harbor Ave', $html);
        $this->assertStringNotContainsString('>Address</', $html);
        $this->assertStringNotContainsString('PostalAddress', $html);
    }

    public function test_contact_inquiry_mail_uses_company_email_when_set(): void
    {
        Mail::fake();

        $this->saveCompanySettings([
            'company_email' => 'inbox@northline.example',
        ]);
        auth()->logout();

        $this->from(route('marketing.contact'))
            ->post(route('marketing.contact.store'), [
                'name' => 'Alex Morgan',
                'email' => 'alex@example.com',
                'company' => 'Northline',
                'phone' => '+1 555 010 2000',
                'message' => 'Please contact me about a demo.',
                'intent' => 'demo',
            ])
            ->assertRedirect();

        Mail::assertQueued(ContactInquiryMail::class, function (ContactInquiryMail $mail) {
            return $mail->hasTo('inbox@northline.example');
        });
    }

    public function test_contact_inquiry_mail_falls_back_to_marketing_email_when_company_email_empty(): void
    {
        Mail::fake();

        $this->saveCompanySettings([
            'company_email' => '',
        ]);
        auth()->logout();

        $fallback = (string) config('marketing.contact.email');

        $this->from(route('marketing.contact'))
            ->post(route('marketing.contact.store'), [
                'name' => 'Alex Morgan',
                'email' => 'alex@example.com',
                'company' => 'Northline',
                'phone' => '+1 555 010 2000',
                'message' => 'Please contact me about a demo.',
                'intent' => 'general',
            ])
            ->assertRedirect();

        Mail::assertQueued(ContactInquiryMail::class, function (ContactInquiryMail $mail) use ($fallback) {
            return $mail->hasTo($fallback);
        });
    }

    public function test_non_super_admin_cannot_update_company_details(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->put(route('superadmin.settings.update'), $this->validSettingsPayload([
                'company_address' => 'Should Not Save',
            ]))
            ->assertForbidden();

        $this->assertNull(PlatformSetting::query()->where('key', 'company_address')->value('value'));
    }

    public function test_guest_cannot_update_company_details(): void
    {
        $this->put(route('superadmin.settings.update'), $this->validSettingsPayload([
            'company_address' => 'Should Not Save',
        ]))->assertRedirect();

        $this->assertNull(PlatformSetting::query()->where('key', 'company_address')->value('value'));
    }

    public function test_social_hash_placeholders_are_not_rendered_as_links_in_footer(): void
    {
        config([
            'marketing.social.linkedin' => '#',
            'marketing.social.twitter' => '#',
            'marketing.social.github' => '#',
            'marketing.social.facebook' => null,
        ]);

        $this->saveCompanySettings([
            'company_linkedin_url' => '',
            'company_twitter_url' => '',
            'company_github_url' => '',
            'company_facebook_url' => '',
        ]);
        auth()->logout();

        $html = $this->get(route('marketing.home'))->assertOk()->getContent();

        $this->assertStringNotContainsString('href="#"', $html);
        $this->assertStringNotContainsString('aria-label="LinkedIn"', $html);
    }

    public function test_filled_social_links_render_in_footer_and_contact_page(): void
    {
        $this->saveCompanySettings([
            'company_linkedin_url' => 'https://www.linkedin.com/company/northline',
            'company_twitter_url' => 'https://x.com/northline',
        ]);
        auth()->logout();

        $this->get(route('marketing.home'))
            ->assertOk()
            ->assertSee('https://www.linkedin.com/company/northline', false)
            ->assertSee('https://x.com/northline', false)
            ->assertSee('aria-label="LinkedIn"', false);

        $this->get(route('marketing.contact'))
            ->assertOk()
            ->assertSee('https://www.linkedin.com/company/northline', false)
            ->assertSee('https://x.com/northline', false);
    }
}
