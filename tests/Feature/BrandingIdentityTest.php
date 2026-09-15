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

    public function test_http_error_pages_are_branded_nexacrm(): void
    {
        $this->get('/this-nexacrm-page-does-not-exist')
            ->assertNotFound()
            ->assertSee('NexaCRM', false)
            ->assertSee('Page not found', false)
            ->assertDontSee('algos', false);
    }
}
