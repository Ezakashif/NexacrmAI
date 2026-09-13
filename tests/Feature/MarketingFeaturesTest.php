<?php

namespace Tests\Feature;

use Tests\TestCase;

class MarketingFeaturesTest extends TestCase
{
    public function test_features_page_lists_all_crm_modules(): void
    {
        $response = $this->get(route('marketing.features'));

        $response->assertOk()
            ->assertSee(config('marketing.features_page.headline'))
            ->assertSee('Lead management')
            ->assertSee('Customer management')
            ->assertSee('Task management')
            ->assertSee('Kanban boards')
            ->assertSee('Reports')
            ->assertSee('Dashboard analytics')
            ->assertSee('Role & permission management')
            ->assertSee('Activity logs')
            ->assertSee('CSV import / export')
            ->assertSee('Global search')
            ->assertSee('Notifications')
            ->assertSee('Company management')
            ->assertSee('Super Admin')
            ->assertSee('Multi-tenant architecture');
    }

    public function test_features_page_groups_modules_for_scanning(): void
    {
        $this->get(route('marketing.features'))
            ->assertOk()
            ->assertSee('Sales & relationships')
            ->assertSee('Execution & collaboration')
            ->assertSee('Insights & data')
            ->assertSee('Platform & administration');
    }
}
