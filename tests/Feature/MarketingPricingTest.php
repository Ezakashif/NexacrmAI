<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\PlanFeature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketingPricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_pricing_page_shows_plans_and_toggle_copy(): void
    {
        Plan::withTrashed()->forceDelete();

        $starter = Plan::factory()->public()->create(['name' => 'Starter', 'slug' => 'starter', 'is_free' => true]);
        $professional = Plan::factory()->public()->create(['name' => 'Professional', 'slug' => 'professional', 'is_featured' => true]);
        Plan::factory()->public()->create(['name' => 'Enterprise', 'slug' => 'enterprise']);
        $professional->features()->create(PlanFeature::factory()->make(['feature_key' => 'reports', 'feature_name' => 'Reports'])->toArray());

        $this->get(route('marketing.pricing'))
            ->assertOk()
            ->assertSee(config('marketing.pricing.headline'))
            ->assertSee('Starter')
            ->assertSee('Professional')
            ->assertSee('Enterprise')
            ->assertSee('Monthly')
            ->assertSee('Annual')
            ->assertSee(config('marketing.pricing.annual_discount_label'))
            ->assertSee('free trial', false)
            ->assertSee('Contact Sales');
    }

    public function test_pricing_page_includes_comparison_and_faq(): void
    {
        Plan::withTrashed()->forceDelete();

        $plan = Plan::factory()->public()->create(['name' => 'Professional', 'slug' => 'professional', 'is_featured' => true]);
        $plan->features()->create(PlanFeature::factory()->make(['feature_key' => 'lead-management', 'feature_name' => 'Lead management'])->toArray());

        $this->get(route('marketing.pricing'))
            ->assertOk()
            ->assertSee('Feature comparison')
            ->assertSee('Lead management')
            ->assertSee('Lead management')
            ->assertSee('Pricing questions')
            ->assertSee('Do I need a credit card to start?')
            ->assertSee('Documentation')
            ->assertSee('Contact support')
            ->assertSee('Book a demo')
            ->assertSee(config('marketing.pricing.future_note'))
            ->assertSee('No credit card required', false)
            ->assertSee('Recommended', false);
    }

    public function test_pricing_page_uses_platform_trial_duration_for_free_plans(): void
    {
        Plan::withTrashed()->forceDelete();

        app(\App\Services\SuperAdmin\PlatformSettingsService::class)->setMany([
            'registration_enabled' => true,
            'trial_duration_days' => 30,
        ]);

        Plan::factory()->public()->create([
            'name' => 'Starter',
            'slug' => 'starter',
            'is_free' => true,
            'trial_days' => 7,
            'is_featured' => true,
        ]);

        $this->get(route('marketing.pricing'))
            ->assertOk()
            ->assertSee('Start 30 days free trial', false)
            ->assertSee('30 days free trial', false)
            ->assertDontSee('Start 7 days free trial', false);
    }
}
