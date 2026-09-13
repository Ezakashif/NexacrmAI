<?php

namespace App\Support;

use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class RateLimitResponse
{
    public static function fromException(
        TooManyRequestsHttpException|ThrottleRequestsException $exception,
        Request $request,
    ): JsonResponse|RedirectResponse {
        $seconds = self::retryAfterSeconds($exception);
        $message = self::messageFor($request, $seconds);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => $message,
                'retry_after' => $seconds,
            ], 429);
        }

        $redirect = redirect()
            ->back(fallback: self::fallbackUrl($request))
            ->with('warning', $message);

        return $redirect;
    }

    public static function retryAfterSeconds(TooManyRequestsHttpException|ThrottleRequestsException $exception): int
    {
        $headers = $exception->getHeaders();
        $retryAfter = $headers['Retry-After'] ?? $headers['retry-after'] ?? null;

        if (is_numeric($retryAfter)) {
            return max(1, (int) $retryAfter);
        }

        return 60;
    }

    public static function messageFor(Request $request, int $seconds): string
    {
        $wait = self::waitLabel($seconds);
        $route = $request->route()?->getName();

        return match ($route) {
            'verification.send' => "You've requested too many verification emails. Please wait {$wait} before trying again.",
            'password.email' => "You've requested too many password reset emails. Please wait {$wait} before trying again.",
            'password.store' => "Too many password reset attempts. Please wait {$wait} before trying again.",
            'login.store' => "Too many login attempts. Please wait {$wait} before trying again.",
            'invitations.accept.store' => "Too many invitation attempts. Please wait {$wait} before trying again.",
            'marketing.contact.store' => "You've submitted this form too many times. Please wait {$wait} before trying again.",
            'superadmin.email-templates.test' => "Too many test emails sent. Please wait {$wait} before trying again.",
            default => "You're doing that too quickly. Please wait {$wait} before trying again.",
        };
    }

    public static function waitLabel(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds.' '.str('second')->plural($seconds);
        }

        $minutes = (int) ceil($seconds / 60);

        return $minutes.' '.str('minute')->plural($minutes);
    }

    private static function fallbackUrl(Request $request): string
    {
        return match ($request->route()?->getName()) {
            'verification.send' => route('verification.notice'),
            'password.email' => route('password.request'),
            'password.store' => url()->previous() ?: route('password.request'),
            'login.store' => route('login'),
            'marketing.contact.store' => route('marketing.contact'),
            default => url('/'),
        };
    }
}
