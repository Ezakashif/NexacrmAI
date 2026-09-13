<?php

namespace App\Providers;

use App\Auth\TenantUserProvider;
use App\Models\User;
use App\Services\PermissionRegistrar;
use App\Services\SuperAdmin\PlatformSettingsService;
use App\Support\CurrentCompany;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(CurrentCompany::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFour();

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        Password::defaults(function () {
            // Shared by register, invites, resets, and admin provisioning.
            return Password::min(10)
                ->mixedCase()
                ->symbols();
        });

        Auth::provider('tenant-eloquent', function ($app, array $config) {
            return new TenantUserProvider($app['hash'], $config['model']);
        });

        ResetPassword::createUrlUsing(function (User $user, string $token) {
            return url(route('password.reset', [
                'token' => $token,
                'email' => $user->email,
            ], false));
        });

        app(PermissionRegistrar::class)->registerGates();

        RateLimiter::for('website-leads', function (Request $request) {
            return Limit::perMinute(config('website_leads.rate_limit', 10))
                ->by($request->ip());
        });

        RateLimiter::for('channel-webhooks', function (Request $request) {
            return Limit::perMinute((int) config('channels.webhooks.rate_limit', 60))
                ->by($request->ip().'|'.(string) $request->route('uuid'));
        });

        $this->applyPlatformBranding();
    }

    private function applyPlatformBranding(): void
    {
        try {
            if (! Schema::hasTable('platform_settings')) {
                return;
            }

            app(PlatformSettingsService::class)->applyBranding();
        } catch (\Throwable) {
            // Ignore during early bootstrap / migrate when DB is unavailable.
        }
    }
}
