<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Notifications\WelcomeNotification;
use App\Services\ActivityLogger;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));

            ActivityLogger::log('email.verified', $user, [
                'email' => $user->email,
                'user_agent' => $request->userAgent(),
            ], $user->id);

            $companyName = $user->company?->name ?? config('app.name');
            $user->notify(new WelcomeNotification($companyName));
        }

        return redirect()
            ->intended(route('dashboard', absolute: false).'?verified=1')
            ->with('success', 'Your email has been verified. Welcome to your workspace.');
    }
}
