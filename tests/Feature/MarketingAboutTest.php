<?php

namespace Tests\Feature;

use Tests\TestCase;

class MarketingAboutTest extends TestCase
{
    public function test_about_page_covers_required_sections(): void
    {
        $this->get(route('marketing.about'))
            ->assertOk()
            ->assertSee(config('marketing.about.headline'))
            ->assertSee('Mission')
            ->assertSee(config('marketing.about.mission.body'))
            ->assertSee('Vision')
            ->assertSee(config('marketing.about.vision.body'))
            ->assertSee('Why we built Algos')
            ->assertSee('How Algos took shape')
            ->assertSee('The stack behind Algos')
            ->assertSee('Laravel')
            ->assertSee('Tailwind CSS')
            ->assertSee('Alpine.js');
    }

    public function test_about_page_includes_timeline_milestones(): void
    {
        $response = $this->get(route('marketing.about'))->assertOk();

        foreach (config('marketing.about.timeline') as $item) {
            $response->assertSee($item['title']);
            $response->assertSee($item['year']);
        }
    }
}
