<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Company;
use App\Services\ActivityLogger;
use App\Services\SuperAdmin\PlatformSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(PlatformSettingsService $settings): View
    {
        return view('auth.login', [
            'registrationEnabled' => $settings->getBool('registration_enabled'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request, PlatformSettingsService $settings): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = auth()->user();

        if ($user?->isSuperAdmin()) {
            $this->recordSuccessfulLogin($user, $request);
            ActivityLogger::log('user.login', $user, [
                'user_agent' => $request->userAgent(),
            ]);

            return redirect()->intended(route('superadmin.dashboard', absolute: false));
        }

        if ($settings->getBool('maintenance_mode')) {
            return $this->rejectAuthenticatedSession(
                $request,
                'The platform is temporarily unavailable for maintenance. Please try again later.',
            );
        }

        $company = $user?->company_id
            ? Company::query()->find($user->company_id)
            : null;

        if ($company && ! $company->isActive()) {
            return $this->rejectAuthenticatedSession(
                $request,
                'Your company account is suspended. Please contact support.',
            );
        }

        if ($company && $company->isSubscriptionExpired()) {
            $message = $company->expiredAccessMessage();
            $this->markSubscriptionExpired($company);

            return $this->rejectAuthenticatedSession($request, $message);
        }

        if ($user) {
            $this->recordSuccessfulLogin($user, $request);
            ActivityLogger::log('user.login', $user, [
                'user_agent' => $request->userAgent(),
            ]);
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function recordSuccessfulLogin($user, Request $request): void
    {
        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();
    }

    private function rejectAuthenticatedSession(Request $request, string $message): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->withErrors(['email' => $message]);
    }

    private function markSubscriptionExpired(Company $company): void
    {
        if ($company->subscription_status === Company::SUBSCRIPTION_EXPIRED) {
            return;
        }

        $company->forceFill([
            'subscription_status' => Company::SUBSCRIPTION_EXPIRED,
        ])->save();
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        if ($request->user()) {
            ActivityLogger::log('user.logout', $request->user());
            Auth::guard('web')->logout();
        }

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect()->route('login');
    }
}
