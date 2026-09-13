<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\URL;
use Throwable;

class EmailVerification
{
    /**
     * Mailers that never deliver to a real inbox.
     *
     * @var list<string>
     */
    private const NON_DELIVERING_MAILERS = ['log', 'array'];

    public static function signedUrl(User $user): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes((int) config('auth.verification.expire', 60)),
            [
                'id' => $user->getKey(),
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );
    }

    public static function usesNonDeliveringMailer(): bool
    {
        return in_array(strtolower((string) config('mail.default')), self::NON_DELIVERING_MAILERS, true);
    }

    /**
     * When mail is only written to logs/memory, expose a same-session preview link
     * so local/dev (and misconfigured hosts) can still finish verification.
     */
    public static function previewUrlFor(User $user): ?string
    {
        if (! self::usesNonDeliveringMailer()) {
            return null;
        }

        return self::signedUrl($user);
    }

    public static function sendFailureMessage(string $fallback, Throwable $e): string
    {
        if (! config('app.debug')) {
            return $fallback;
        }

        $detail = trim($e->getMessage());

        return $detail !== ''
            ? $fallback.' ('.$detail.')'
            : $fallback;
    }
}
