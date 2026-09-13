<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Notifications\WelcomeNotification;
use App\Services\CompanyProvisioner;
use App\Services\SuperAdmin\PlatformSettingsService;
use App\Support\EmailVerification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Throwable;

class RegisteredUserController extends Controller
{
    public function create(PlatformSettingsService $settings): View
    {
        return view('auth.register', [
            'platformName' => $settings->platformName(),
        ]);
    }

    public function store(
        Request $request,
        CompanyProvisioner $provisioner,
        PlatformSettingsService $settings,
    ): RedirectResponse {
        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $requiresVerification = $settings->emailVerificationRequired();

        $result = $provisioner->provision([
            'name' => $validated['company_name'],
            'status' => $settings->get('default_company_status', Company::STATUS_ACTIVE),
            'subscription_status' => Company::SUBSCRIPTION_TRIAL,
            'admin_name' => $validated['name'],
            'admin_email' => $validated['email'],
            'admin_password' => $validated['password'],
            'mark_admin_email_verified' => ! $requiresVerification,
        ]);

        $admin = $result['admin'];

        $verificationMailError = null;

        try {
            event(new Registered($admin));
        } catch (Throwable $e) {
            // Account is created; don't fail registration if outbound mail is broken.
            report($e);
            $verificationMailError = $e;
        }

        Auth::login($admin);
        $request->session()->regenerate();

        if ($requiresVerification && ! $admin->hasVerifiedEmail()) {
            $redirect = redirect()->route('verification.notice');

            if ($verificationMailError instanceof Throwable) {
                $redirect->withErrors([
                    'email' => EmailVerification::sendFailureMessage(
                        'Your workspace was created, but the verification email could not be sent. Check mail configuration or use Resend.',
                        $verificationMailError
                    ),
                ]);
            } else {
                $redirect->with('status', 'verification-link-sent');
            }

            if ($preview = EmailVerification::previewUrlFor($admin)) {
                $redirect->with('verification_preview_url', $preview);
            }

            return $redirect;
        }

        try {
            $admin->notify(new WelcomeNotification($result['company']->name));
        } catch (Throwable $e) {
            report($e);
        }

        return redirect()
            ->route('dashboard')
            ->with('success', 'Welcome! Your workspace is ready.');
    }
}