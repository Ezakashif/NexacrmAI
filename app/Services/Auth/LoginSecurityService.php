<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LoginSecurityService
{
    public const MAX_ATTEMPTS = 5;

    public const LOCKOUT_MINUTES = 15;

    public function isLocked(User $user): bool
    {
        return $user->locked_until !== null && $user->locked_until->isFuture();
    }

    public function lockoutMessage(User $user): string
    {
        $seconds = max(
            0,
            ($user->locked_until?->getTimestamp() ?? now()->getTimestamp()) - now()->getTimestamp()
        );
        $minutes = max(1, (int) ceil($seconds / 60));

        return "Too many failed sign-in attempts. Try again in {$minutes} minute".($minutes === 1 ? '' : 's').'.';
    }

    /**
     * @throws ValidationException
     */
    public function assertNotLocked(User $user): void
    {
        if ($this->isLocked($user)) {
            throw ValidationException::withMessages([
                'email' => $this->lockoutMessage($user),
            ]);
        }

        if ($user->locked_until !== null && $user->locked_until->isPast()) {
            $this->clearFailures($user);
        }
    }

    /**
     * @return bool True when this failure newly locked the account.
     */
    public function recordFailedAttempt(User $user, Request $request): bool
    {
        $attempts = (int) $user->failed_login_attempts + 1;
        $newlyLocked = false;

        $payload = [
            'failed_login_attempts' => $attempts,
        ];

        if ($attempts >= self::MAX_ATTEMPTS) {
            $payload['locked_until'] = now()->addMinutes(self::LOCKOUT_MINUTES);
            $newlyLocked = true;
        }

        $user->forceFill($payload)->save();

        ActivityLogger::log('user.login_failed', $user, [
            'email' => $user->email,
            'attempts' => $attempts,
            'user_agent' => $request->userAgent(),
        ], $user->id);

        if ($newlyLocked) {
            ActivityLogger::log('user.locked_out', $user, [
                'email' => $user->email,
                'locked_until' => $user->fresh()->locked_until?->toIso8601String(),
                'user_agent' => $request->userAgent(),
            ], $user->id);
        }

        return $newlyLocked;
    }

    public function clearFailures(User $user): void
    {
        if ((int) $user->failed_login_attempts === 0 && $user->locked_until === null) {
            return;
        }

        $user->forceFill([
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ])->save();
    }

    public function logUnknownEmailFailure(Request $request, string $email): void
    {
        ActivityLogger::log('user.login_failed', null, [
            'email' => $email,
            'unknown_account' => true,
            'user_agent' => $request->userAgent(),
        ]);
    }
}
