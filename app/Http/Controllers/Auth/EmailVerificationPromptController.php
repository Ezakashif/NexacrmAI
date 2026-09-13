<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\SuperAdmin\PlatformSettingsService;
use App\Support\EmailVerification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationPromptController extends Controller
{
    /**
     * Display the email verification prompt.
     */
    public function __invoke(Request $request, PlatformSettingsService $settings): RedirectResponse|View
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail() || ! $settings->emailVerificationRequired()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        return view('auth.verify-email', [
            'email' => $user->email,
            'verificationPreviewUrl' => session('verification_preview_url')
                ?? EmailVerification::previewUrlFor($user),
            'mailDeliveryDisabled' => EmailVerification::usesNonDeliveringMailer(),
        ]);
    }
}
