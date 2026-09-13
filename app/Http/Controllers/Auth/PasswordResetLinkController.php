<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Support\DemoEnvironment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Generic success copy — never reveal whether the email exists.
     */
    public const REQUEST_STATUS_MESSAGE = 'If that email is associated with an account, a password reset link has been sent.';

    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     */
    public function store(ForgotPasswordRequest $request): RedirectResponse
    {
        $email = (string) $request->validated('email');

        if (DemoEnvironment::isProtectedDemoEmail($email)) {
            return back()->with('status', self::REQUEST_STATUS_MESSAGE);
        }

        $status = Password::sendResetLink([
            'email' => $email,
        ]);

        $user = User::withoutCompanyScope()
            ->where('email', $email)
            ->first();

        if ($user && $status === Password::RESET_LINK_SENT) {
            ActivityLogger::log('password.reset_requested', $user, [
                'email' => $user->email,
                'user_agent' => $request->userAgent(),
            ], $user->id);
        }

        return back()->with('status', self::REQUEST_STATUS_MESSAGE);
    }
}
