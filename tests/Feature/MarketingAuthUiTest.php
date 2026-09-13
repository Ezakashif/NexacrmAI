<?php

namespace Tests\Feature;

use App\Services\SuperAdmin\PlatformSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketingAuthUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_uses_marketing_auth_chrome(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Sign in')
            ->assertSee('Back to website')
            ->assertSee(route('marketing.home'), false)
            ->assertDontSee('adminlte', false)
            ->assertSee('noindex,nofollow', false)
            ->assertSee('Try Live Demo', false);
    }

    public function test_forgot_password_uses_marketing_auth_chrome(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Forgot your password?')
            ->assertSee('Email reset link');
    }

    public function test_register_uses_marketing_auth_chrome_when_enabled(): void
    {
        app(PlatformSettingsService::class)->setMany(['registration_enabled' => true]);

        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Create your workspace')
            ->assertSee('Company name')
            ->assertSee('Create account');
    }
}
