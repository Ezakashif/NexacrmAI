<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SuperAdmin\PlatformSettingsService;
use App\Support\RateLimitResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RateLimitFriendlyResponseTest extends TestCase
{
    use RefreshDatabase;

    public function test_verification_resend_throttle_shows_friendly_warning(): void
    {
        Notification::fake();
        app(PlatformSettingsService::class)->setMany(['email_verification_required' => true]);

        $user = User::factory()->unverified()->create();

        $this->actingAs($user);

        for ($i = 0; $i < 6; $i++) {
            $this->post(route('verification.send'))->assertRedirect();
        }

        $this->from(route('verification.notice'))
            ->post(route('verification.send'))
            ->assertRedirect(route('verification.notice'))
            ->assertSessionHas('warning', function (string $message) {
                return str_contains($message, 'verification emails')
                    && str_contains($message, 'wait');
            });
    }

    public function test_password_reset_throttle_shows_friendly_warning(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        for ($i = 0; $i < 6; $i++) {
            $this->post(route('password.email'), ['email' => $user->email])->assertRedirect();
        }

        $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('warning', function (string $message) {
                return str_contains($message, 'password reset')
                    && str_contains($message, 'wait');
            });
    }

    public function test_json_throttle_returns_friendly_payload(): void
    {
        Notification::fake();
        app(PlatformSettingsService::class)->setMany(['email_verification_required' => true]);

        $user = User::factory()->unverified()->create();
        $this->actingAs($user);

        for ($i = 0; $i < 6; $i++) {
            $this->post(route('verification.send'))->assertRedirect();
        }

        $response = $this->postJson(route('verification.send'));

        $response->assertStatus(429)
            ->assertJsonStructure(['message', 'retry_after']);

        $this->assertStringContainsString('verification emails', $response->json('message'));
        $this->assertIsInt($response->json('retry_after'));
        $this->assertGreaterThan(0, $response->json('retry_after'));
    }

    public function test_wait_label_uses_seconds_and_minutes(): void
    {
        $this->assertSame('1 second', RateLimitResponse::waitLabel(1));
        $this->assertSame('45 seconds', RateLimitResponse::waitLabel(45));
        $this->assertSame('1 minute', RateLimitResponse::waitLabel(60));
        $this->assertSame('2 minutes', RateLimitResponse::waitLabel(90));
    }
}
