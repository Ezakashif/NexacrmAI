<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Services\Auth\LoginSecurityService;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        /** @var LoginSecurityService $security */
        $security = app(LoginSecurityService::class);

        $email = (string) $this->input('email');

        $user = User::withoutCompanyScope()
            ->where('email', $email)
            ->first();

        // Persistent lockout takes priority over short-lived cache throttling.
        if ($user) {
            $security->assertNotLocked($user);
        }

        $this->ensureIsNotRateLimited();

        $passwordValid = $user && Hash::check((string) $this->input('password'), $user->password);

        if (! $passwordValid) {
            RateLimiter::hit($this->throttleKey());

            if ($user) {
                $newlyLocked = $security->recordFailedAttempt($user, $this);
                $fresh = $user->fresh();

                if ($newlyLocked || $security->isLocked($fresh)) {
                    throw ValidationException::withMessages([
                        'email' => $security->lockoutMessage($fresh),
                    ]);
                }
            } else {
                $security->logUnknownEmailFailure($this, $email);
            }

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        // Soften account-status enumeration: same message as bad credentials.
        if (! $user->isActive()) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        $security->clearFailures($user);

        Auth::login($user, $this->boolean('remember'));

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
