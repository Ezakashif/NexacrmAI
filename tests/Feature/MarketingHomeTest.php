<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SuperAdmin\PlatformSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketingHomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_marketing_home(): void
    {
        app(PlatformSettingsService::class)->setMany([
            'trial_duration_days' => 14,
            'registration_enabled' => true,
        ]);

        $this->get(route('marketing.home'))
            ->assertOk()
            ->assertSee(config('marketing.home.headline'), false)
            ->assertSee('Start 14 days free trial', false)
            ->assertSee('Try Live Demo', false)
            ->assertSee('Watch Demo', false)
            ->assertSee(route('marketing.watch-demo'), false)
            ->assertSee('No credit card required', false)
            ->assertSee('The CRM foundation your team can rely on', false)
            ->assertSee('See the CRM in Action', false)
            ->assertSee('Outcomes for every stage of the pipeline', false)
            ->assertSee('One connected workflow from first lead to clear decisions', false)
            ->assertSee('Lead created', false)
            ->assertSeeText('Reports & analytics')
            ->assertSee('Why NexaCRM AI', false)
            ->assertSee('The structure to grow without the CRM complexity', false)
            ->assertSee('Leave spreadsheets behind', false)
            ->assertSee('Plans that scale with your team', false)
            ->assertSee('Questions, answered', false)
            ->assertSee('Documentation', false)
            ->assertSee('Contact support', false)
            ->assertSee('Ready to organize your sales pipeline?', false);
    }

    public function test_home_hides_free_trial_ctas_when_registration_is_disabled(): void
    {
        app(PlatformSettingsService::class)->setMany([
            'registration_enabled' => false,
            'trial_duration_days' => 14,
        ]);

        $this->get(route('marketing.home'))
            ->assertOk()
            ->assertDontSee('Start 14 days free trial', false)
            ->assertDontSee('Start Free Trial', false)
            ->assertDontSee('href="'.route('register').'"', false)
            ->assertSee('Try Live Demo', false)
            ->assertSee('Watch Demo', false)
            ->assertSee(route('demo.select'), false)
            ->assertSee(route('marketing.watch-demo'), false);
    }

    public function test_home_uses_the_super_admin_trial_duration_in_trust_cta(): void
    {
        app(PlatformSettingsService::class)->setMany([
            'trial_duration_days' => 21,
            'registration_enabled' => true,
        ]);

        $this->get(route('marketing.home'))
            ->assertOk()
            ->assertSee('Start 21 days free trial', false)
            ->assertSee('21 days free trial', false)
            ->assertDontSee('Start free trial', false)
            ->assertDontSee('30-day free trial', false);
    }

    public function test_home_uses_singular_day_in_trial_ctas(): void
    {
        app(PlatformSettingsService::class)->setMany([
            'trial_duration_days' => 1,
            'registration_enabled' => true,
        ]);

        $this->get(route('marketing.home'))
            ->assertOk()
            ->assertSee('Start 1 day free trial', false)
            ->assertDontSee('Start 1 days free trial', false);
    }

    public function test_home_includes_dashboard_preview_and_pricing_plans(): void
    {
        $this->get(route('marketing.home'))
            ->assertOk()
            ->assertSee('nexacrm-overview.png', false)
            ->assertSee('Starter', false)
            ->assertSee('Professional', false)
            ->assertSee('Enterprise', false)
            ->assertSee('data-mk-scroll-top', false)
            ->assertSee('mk-hero-shape', false)
            ->assertDontSee('mk-stats-band', false)
            ->assertSee('#product-showcase-heading', false)
            ->assertSee('#contact', false)
            ->assertSee('Multi-tenant SaaS', false)
            ->assertSee('Role-based access', false);
    }

    public function test_home_product_showcase_covers_core_modules(): void
    {
        $response = $this->get(route('marketing.home'))->assertOk();

        foreach (config('marketing.home.product_showcase.items') as $item) {
            $response->assertSeeText($item['title']);
            $response->assertSeeText($item['benefit']);
        }

        $response
            ->assertSee('Explore every part of the CRM', false)
            ->assertSee('marketing/screenshots/', false)
            ->assertDontSee('Screenshot placeholder', false)
            ->assertDontSee('Drop a real', false)
            ->assertSee('Business benefit', false)
            ->assertSee('Privacy Policy', false)
            ->assertSee('Help Center', false);
    }

    public function test_authenticated_user_is_redirected_from_home_to_dashboard(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('marketing.home'))
            ->assertRedirect(route('dashboard'));
    }
}
