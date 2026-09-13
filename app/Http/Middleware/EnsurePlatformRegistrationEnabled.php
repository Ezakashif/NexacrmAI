<?php

namespace App\Http\Middleware;

use App\Services\SuperAdmin\PlatformSettingsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlatformRegistrationEnabled
{
    public function __construct(
        private PlatformSettingsService $settings,
    ) {}

    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->settings->getBool('registration_enabled')) {
            abort(404);
        }

        return $next($request);
    }
}
