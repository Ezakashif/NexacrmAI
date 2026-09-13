<?php

namespace App\Http\Middleware;

use App\Services\SuperAdmin\ImpersonationService;
use App\Services\SuperAdmin\PlatformSettingsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlatformNotInMaintenance
{
    public function __construct(
        private PlatformSettingsService $settings,
    ) {}

    /**
     * Block tenant access while platform maintenance mode is enabled.
     * Super Admins (and active impersonation sessions) can still operate.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->settings->getBool('maintenance_mode')) {
            return $next($request);
        }

        $user = $request->user();

        if ($user?->isSuperAdmin()) {
            return $next($request);
        }

        if (app(ImpersonationService::class)->isImpersonating($request)) {
            return $next($request);
        }

        if ($this->isAllowedDuringMaintenance($request)) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => 'The platform is temporarily unavailable for maintenance.',
            ], 503);
        }

        return response()
            ->view('maintenance', [
                'platformName' => $this->settings->platformName(),
            ], 503);
    }

    private function isAllowedDuringMaintenance(Request $request): bool
    {
        if ($request->is('up') || $request->is('webhooks/*')) {
            return true;
        }

        if ($request->is('superadmin') || $request->is('superadmin/*')) {
            return true;
        }

        // Allow ending an impersonation session even if the flag flipped mid-session.
        if ($request->routeIs('impersonation.leave') || $request->is('impersonation/leave')) {
            return true;
        }

        return $request->routeIs([
            'login',
            'login.store',
            'logout',
            'password.request',
            'password.email',
            'password.reset',
            'password.store',
        ]) || $request->is('login');
    }
}
