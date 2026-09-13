<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Support\DemoEnvironment;
use App\Services\Auth\SessionManager;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    public const RESET_SUCCESS_MESSAGE = 'Your password has been reset. You can sign in with your new password.';

    public const RESET_FAILURE_MESSAGE = 'This password reset link is invalid or has expired. Please request a new one.';

    public function __construct(
        private SessionManager $sessions,
    ) {}

    /**
     * Display the password reset view.
     */
    public function create(Request $request): View
    {
        return view('auth.reset-password', ['request' => $request]);
    }

    /**
     * Handle an incoming new password request.
     *
     * Intentionally does not auto-login after reset. Users must sign in
     * with the new password so shared/reset-link sessions stay safer.
     */
    public function store(ResetPasswordRequest $request): RedirectResponse
    {
        if (DemoEnvironment::isProtectedDemoEmail((string) $request->input('email'))) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => self::RESET_FAILURE_MESSAGE]);
        }

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user) use ($request) {
                $user->forceFill([
                    'password' => $request->validated('password'),
                    'remember_token' => Str::random(60),
                ])->save();

                $this->sessions->destroyAllSessions($user);

                ActivityLogger::log('password.reset', $user, [
                    'email' => $user->email,
                    'user_agent' => $request->userAgent(),
                ], $user->id);

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            if (Auth::check()) {
                Auth::guard('web')->logout();
            }

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            return redirect()
                ->route('login')
                ->with('status', self::RESET_SUCCESS_MESSAGE);
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => self::RESET_FAILURE_MESSAGE]);
    }
}
