<?php

namespace App\Http\Middleware;

use App\Services\SuperAdmin\PlatformSettingsService;
use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailIsVerifiedWhenRequired
{
    public function __construct(
        private PlatformSettingsService $settings,
    ) {}

    /**
     * Enforce email verification for CRM routes when the platform setting is on.
     *
     * Super Admin routes do not use this middleware. Tenant users who are
     * MustVerifyEmail must verify before accessing the CRM when required.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->settings->emailVerificationRequired()) {
            return $next($request);
        }

        $user = $request->user();

        if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Your email address is not verified.'], 409)
                : redirect()->route('verification.notice');
        }

        return $next($request);
    }
}
