<?php

namespace Tests\Unit\Support;

use App\Support\RateLimitResponse;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Tests\TestCase;

class RateLimitResponseTest extends TestCase
{
    public function test_wait_label_uses_seconds_for_under_one_minute(): void
    {
        $this->assertSame('1 second', RateLimitResponse::waitLabel(1));
        $this->assertSame('2 seconds', RateLimitResponse::waitLabel(2));
        $this->assertSame('45 seconds', RateLimitResponse::waitLabel(45));
        $this->assertSame('59 seconds', RateLimitResponse::waitLabel(59));
    }

    public function test_wait_label_uses_minutes_for_sixty_seconds_or_more(): void
    {
        $this->assertSame('1 minute', RateLimitResponse::waitLabel(60));
        $this->assertSame('2 minutes', RateLimitResponse::waitLabel(90));
        $this->assertSame('2 minutes', RateLimitResponse::waitLabel(120));
        $this->assertSame('3 minutes', RateLimitResponse::waitLabel(121));
    }

    public function test_retry_after_seconds_reads_retry_after_header(): void
    {
        $exception = new TooManyRequestsHttpException(45);

        $this->assertSame(45, RateLimitResponse::retryAfterSeconds($exception));
    }

    public function test_retry_after_seconds_reads_lowercase_header(): void
    {
        $exception = new ThrottleRequestsException('', null, ['retry-after' => 30]);

        $this->assertSame(30, RateLimitResponse::retryAfterSeconds($exception));
    }

    public function test_retry_after_seconds_defaults_to_sixty_when_header_missing(): void
    {
        $exception = new ThrottleRequestsException();

        $this->assertSame(60, RateLimitResponse::retryAfterSeconds($exception));
    }

    public function test_retry_after_seconds_enforces_minimum_of_one(): void
    {
        $exception = new ThrottleRequestsException('', null, ['Retry-After' => 0]);

        $this->assertSame(1, RateLimitResponse::retryAfterSeconds($exception));
    }

    /**
     * @dataProvider routeMessageProvider
     */
    public function test_message_for_each_named_route(string $routeName, string $expectedFragment): void
    {
        $request = $this->requestWithRoute($routeName);
        $message = RateLimitResponse::messageFor($request, 45);

        $this->assertStringContainsString($expectedFragment, $message);
        $this->assertStringContainsString('45 seconds', $message);
    }

    public static function routeMessageProvider(): array
    {
        return [
            'verification.send' => ['verification.send', 'verification emails'],
            'password.email' => ['password.email', 'password reset emails'],
            'password.store' => ['password.store', 'password reset attempts'],
            'login.store' => ['login.store', 'login attempts'],
            'invitations.accept.store' => ['invitations.accept.store', 'invitation attempts'],
            'marketing.contact.store' => ['marketing.contact.store', 'submitted this form too many times'],
            'superadmin.email-templates.test' => ['superadmin.email-templates.test', 'test emails sent'],
        ];
    }

    public function test_message_for_unknown_route_uses_default_copy(): void
    {
        $request = $this->requestWithRoute('dashboard');
        $message = RateLimitResponse::messageFor($request, 60);

        $this->assertSame(
            "You're doing that too quickly. Please wait 1 minute before trying again.",
            $message,
        );
    }

    public function test_message_for_request_without_route_uses_default_copy(): void
    {
        $request = Request::create('/unknown', 'POST');
        $message = RateLimitResponse::messageFor($request, 30);

        $this->assertSame(
            "You're doing that too quickly. Please wait 30 seconds before trying again.",
            $message,
        );
    }

    public function test_from_exception_returns_json_for_expects_json_requests(): void
    {
        $request = $this->requestWithRoute('login.store');
        $request->headers->set('Accept', 'application/json');

        $exception = new TooManyRequestsHttpException(25);
        $response = RateLimitResponse::fromException($exception, $request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(429, $response->getStatusCode());
        $this->assertSame([
            'message' => 'Too many login attempts. Please wait 25 seconds before trying again.',
            'retry_after' => 25,
        ], $response->getData(true));
    }

    public function test_from_exception_returns_json_for_ajax_requests(): void
    {
        $request = $this->requestWithRoute('password.email');
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        $exception = new ThrottleRequestsException('', null, ['Retry-After' => 90]);
        $response = RateLimitResponse::fromException($exception, $request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(429, $response->getStatusCode());
        $this->assertStringContainsString('password reset emails', $response->getData(true)['message']);
        $this->assertSame(90, $response->getData(true)['retry_after']);
    }

    public function test_from_exception_returns_redirect_with_warning_for_html_requests(): void
    {
        $request = $this->requestWithRoute('verification.send');
        $session = app('session.store');
        $session->start();
        $request->setLaravelSession($session);

        $exception = new TooManyRequestsHttpException(15);
        $response = RateLimitResponse::fromException($exception, $request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(
            "You've requested too many verification emails. Please wait 15 seconds before trying again.",
            $session->get('warning'),
        );
    }

    private function requestWithRoute(string $routeName): Request
    {
        $request = Request::create('/test', 'POST');
        $request->setRouteResolver(function () use ($routeName) {
            return (new Route('POST', '/test', []))->name($routeName);
        });

        return $request;
    }
}
