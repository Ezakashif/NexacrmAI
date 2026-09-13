<?php

namespace Tests\Feature;

use Tests\TestCase;

class MarketingSeoTest extends TestCase
{
    public function test_marketing_pages_include_core_seo_tags(): void
    {
        $this->get(route('marketing.home'))
            ->assertOk()
            ->assertSee('<meta name="description"', false)
            ->assertSee('<meta property="og:title"', false)
            ->assertSee('<meta name="twitter:card"', false)
            ->assertSee('application/ld+json', false)
            ->assertSee('rel="canonical"', false);
    }

    public function test_sitemap_lists_public_marketing_routes(): void
    {
        $this->get(route('marketing.sitemap'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml')
            ->assertSee(route('marketing.home'), false)
            ->assertSee(route('marketing.features'), false)
            ->assertSee(route('marketing.pricing'), false)
            ->assertSee(route('marketing.documentation'), false)
            ->assertSee(route('marketing.about'), false)
            ->assertSee(route('marketing.watch-demo'), false)
            ->assertSee(route('marketing.contact'), false);
    }

    public function test_robots_txt_references_sitemap_and_disallows_auth(): void
    {
        $this->get(route('marketing.robots'))
            ->assertOk()
            ->assertSee('Sitemap:', false)
            ->assertSee('/sitemap.xml', false)
            ->assertSee('Disallow: /login', false)
            ->assertSee('Disallow: /dashboard', false);
    }
}
