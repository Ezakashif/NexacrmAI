<?php

namespace Tests\Unit\Support;

use App\Models\User;
use App\Support\EmailVerification;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_uses_non_delivering_mailer_for_log_and_array_mailers(): void
    {
        config(['mail.default' => 'log']);
        $this->assertTrue(EmailVerification::usesNonDeliveringMailer());

        config(['mail.default' => 'array']);
        $this->assertTrue(EmailVerification::usesNonDeliveringMailer());

        config(['mail.default' => 'LOG']);
        $this->assertTrue(EmailVerification::usesNonDeliveringMailer());
    }

    public function test_uses_non_delivering_mailer_is_false_for_smtp(): void
    {
        config(['mail.default' => 'smtp']);

        $this->assertFalse(EmailVerification::usesNonDeliveringMailer());
    }

    public function test_preview_url_is_null_when_mailer_delivers_for_real(): void
    {
        config(['mail.default' => 'smtp']);
        $user = User::factory()->unverified()->create();

        $this->assertNull(EmailVerification::previewUrlFor($user));
    }

    public function test_preview_url_returns_signed_verification_link_for_non_delivering_mailers(): void
    {
        config(['mail.default' => 'array']);
        $user = User::factory()->unverified()->create();

        $previewUrl = EmailVerification::previewUrlFor($user);

        $this->assertNotNull($previewUrl);
        $this->assertSame(EmailVerification::signedUrl($user), $previewUrl);
        $this->assertStringContainsString('signature=', $previewUrl);
    }

    public function test_signed_url_generates_temporary_signed_verification_route(): void
    {
        URL::forceRootUrl('https://crm.test');
        config(['auth.verification.expire' => 45]);

        $user = User::factory()->unverified()->create(['email' => 'verify@example.com']);

        $url = EmailVerification::signedUrl($user);

        $this->assertStringContainsString('/verify-email/', $url);
        $this->assertStringContainsString((string) $user->id, $url);
        $this->assertStringContainsString(sha1('verify@example.com'), $url);
        $this->assertStringContainsString('expires=', $url);
        $this->assertStringContainsString('signature=', $url);
    }

    public function test_send_failure_message_returns_fallback_when_debug_disabled(): void
    {
        config(['app.debug' => false]);

        $message = EmailVerification::sendFailureMessage(
            'We could not send the verification email.',
            new Exception('SMTP connection refused')
        );

        $this->assertSame('We could not send the verification email.', $message);
    }

    public function test_send_failure_message_appends_exception_detail_when_debug_enabled(): void
    {
        config(['app.debug' => true]);

        $message = EmailVerification::sendFailureMessage(
            'We could not send the verification email.',
            new Exception('SMTP connection refused')
        );

        $this->assertSame(
            'We could not send the verification email. (SMTP connection refused)',
            $message
        );
    }

    public function test_send_failure_message_returns_fallback_when_debug_enabled_but_exception_has_no_message(): void
    {
        config(['app.debug' => true]);

        $message = EmailVerification::sendFailureMessage(
            'We could not send the verification email.',
            new Exception('')
        );

        $this->assertSame('We could not send the verification email.', $message);
    }
}
