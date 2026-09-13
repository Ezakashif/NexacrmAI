<?php

namespace Tests\Feature;

use Tests\TestCase;

class MarketingRoutesTest extends TestCase
{
    public function test_marketing_nav_routes_resolve(): void
    {
        $this->get(route('marketing.features'))->assertOk()->assertSee('Every module your revenue team needs', false);
        $this->get(route('marketing.pricing'))->assertOk()->assertSee(config('marketing.pricing.headline'));
        $this->get(route('marketing.documentation'))->assertOk()->assertSee('CRM Documentation', false);
        $this->get(route('marketing.about'))->assertOk()->assertSee(config('marketing.about.headline'));
        $this->get(route('marketing.contact'))->assertOk()->assertSee('Talk with our team');
        $this->get(route('marketing.watch-demo'))
            ->assertOk()
            ->assertSee('See Algos CRM in action', false)
            ->assertSee('marketing/videos/algos-crm-product-demo.mp4', false);
    }
}
