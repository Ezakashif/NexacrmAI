<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandingIdentityTest extends TestCase
{
    use RefreshDatabase;

    public function test_packaged_nexacrm_branding_assets_exist(): void
    {
        $this->assertFileExists(public_path(config('marketing.assets.logo')));
        $this->assertFileExists(public_path(config('marketing.assets.logo_light')));
        $this->assertFileExists(public_path(config('marketing.assets.logo_svg')));
        $this->assertFileExists(public_path(config('marketing.assets.favicon')));
        $this->assertFileDoesNotExist(public_path('branding/algos-logo.png'));
        $this->assertFileDoesNotExist(public_path('branding/algos-logo.svg'));
        $this->assertFileDoesNotExist(public_path('branding/algos-logo-light.png'));
    }

    public function test_super_admin_chrome_uses_nexacrm_wordmark_not_algos(): void
    {
        $this->seed(RbacSeeder::class);
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->get(route('superadmin.dashboard'))
            ->assertOk()
            ->assertSee('Platform overview', false)
            ->assertSee('branding/nexacrm-logo-light.png', false)
            ->assertSee('NexaCRM', false)
            ->assertDontSee('algos.', false)
            ->assertDontSee('algos-logo', false);
    }

    public function test_marketing_and_login_pages_do_not_reference_algos_assets(): void
    {
        $home = $this->get(route('marketing.home'))->assertOk();
        $home->assertSee('branding/nexacrm-logo.png', false);
        $home->assertSee('branding/nexacrm-mark.svg', false);
        $home->assertDontSee('algos-logo', false);
        $home->assertDontSee('algos-crm', false);

        $login = $this->get(route('login'))->assertOk();
        $login->assertDontSee('algos-logo', false);
        $login->assertDontSee('algos.', false);
        $login->assertSee('NexaCRM', false);
    }

    public function test_buyer_facing_screenshots_use_nexacrm_filenames(): void
    {
        $screenshots = [
            'nexacrm-dashboard.png',
            'nexacrm-overview.png',
            'nexacrm-leads.png',
            'nexacrm-sales-pipeline.png',
            'nexacrm-customers.png',
            'nexacrm-tasks.png',
            'nexacrm-reports.png',
            'nexacrm-analytics.png',
            'nexacrm-activity-log.png',
            'nexacrm-roles-permissions.png',
            'nexacrm-user-management.png',
            'nexacrm-pricing.png',
        ];

        foreach ($screenshots as $file) {
            $this->assertFileExists(public_path('marketing/screenshots/'.$file));
        }

        $this->assertFileExists(public_path('marketing/videos/nexacrm-product-demo.mp4'));
        $this->assertFileExists(public_path('branding/nexacrm-linkedin-cover.png'));

        foreach ([
            'dashboard.PNG',
            'leads.PNG',
            'customers.PNG',
            'tasks.PNG',
            'reports.PNG',
            'dashboard_analytics.PNG',
            'activity_log.PNG',
            'roles_and_permissions.PNG',
            'user_management.PNG',
            'overview.PNG',
            'pricing.PNG',
            'sales_pipeline.png',
        ] as $legacy) {
            $this->assertFileDoesNotExist(public_path('marketing/screenshots/'.$legacy));
        }

        $home = $this->get(route('marketing.home'))->assertOk();
        $home->assertSee('nexacrm-overview.png', false);
        $home->assertSee('nexacrm-dashboard.png', false);
        $home->assertDontSee('overview.PNG', false);
        $home->assertDontSee('dashboard.PNG', false);
        $home->assertDontSee('algos-logo', false);
    }

    public function test_http_error_pages_are_branded_nexacrm(): void
    {
        $this->get('/this-nexacrm-page-does-not-exist')
            ->assertNotFound()
            ->assertSee('NexaCRM', false)
            ->assertSee('Page not found', false)
            ->assertDontSee('algos', false);
    }
}
