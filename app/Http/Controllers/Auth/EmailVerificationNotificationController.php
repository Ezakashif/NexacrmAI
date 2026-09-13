<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use App\Services\SuperAdmin\PlatformSettingsService;
use App\Support\EmailVerification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     */
    public function store(Request $request, PlatformSettingsService $settings): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail() || ! $settings->emailVerificationRequired()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        try {
            $user->sendEmailVerificationNotification();
        } catch (Throwable $e) {
            report($e);

            return back()->withErrors([
                'email' => EmailVerification::sendFailureMessage(
                    'We could not send the verification email. Check your mail configuration and try again.',
                    $e
                ),
            ]);
        }

        ActivityLogger::log('email.verification_resent', $user, [
            'email' => $user->email,
            'user_agent' => $request->userAgent(),
            'mailer' => config('mail.default'),
        ], $user->id);

        $redirect = back()->with('status', 'verification-link-sent');

        if ($preview = EmailVerification::previewUrlFor($user)) {
            $redirect->with('verification_preview_url', $preview);
        }

        return $redirect;
    }
}
