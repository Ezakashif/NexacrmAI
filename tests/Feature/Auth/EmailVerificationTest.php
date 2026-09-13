<?php

namespace Tests\Feature\Auth;

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\SuperAdmin\PlatformSettingsService;
use Illuminate\Auth\Events\Verified;
use App\Notifications\AccountActivationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_screen_can_be_rendered(): void
    {
        app(PlatformSettingsService::class)->setMany(['email_verification_required' => true]);

        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/verify-email');

        $response->assertStatus(200);
        $response->assertSee($user->email, false);
        $response->assertSee('Verification preview link', false);
        $response->assertSee(route('verification.verify', [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ], false), false);
    }

    public function test_email_can_be_verified(): void
    {
        app(PlatformSettingsService::class)->setMany(['email_verification_required' => true]);

        $user = User::factory()->unverified()->create();

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'email.verified',
        ]);
    }

    public function test_email_is_not_verified_with_invalid_hash(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('wrong-email')]
        );

        $this->actingAs($user)->get($verificationUrl);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_verification_can_be_resent(): void
    {
        Notification::fake();
        app(PlatformSettingsService::class)->setMany(['email_verification_required' => true]);

        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->post(route('verification.send'))
            ->assertRedirect()
            ->assertSessionHas('status', 'verification-link-sent')
            ->assertSessionHas('verification_preview_url');

        Notification::assertSentTo($user, AccountActivationNotification::class);

        $this->assertTrue(
            ActivityLog::withoutCompanyScope()
                ->where('user_id', $user->id)
                ->where('action', 'email.verification_resent')
                ->exists()
        );
    }

    public function test_verification_preview_link_is_hidden_when_smtp_mailer_is_configured(): void
    {
        config(['mail.default' => 'smtp']);
        app(PlatformSettingsService::class)->setMany(['email_verification_required' => true]);

        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get('/verify-email')
            ->assertOk()
            ->assertDontSee('Verification preview link', false);
    }

    public function test_unverified_user_can_access_crm_when_verification_disabled(): void
    {
        app(PlatformSettingsService::class)->setMany(['email_verification_required' => false]);

        $user = User::factory()->unverified()->admin()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_public_registration_skips_verification_when_disabled(): void
    {
        Notification::fake();

        app(PlatformSettingsService::class)->setMany([
            'registration_enabled' => true,
            'email_verification_required' => false,
        ]);

        $this->post(route('register'), [
            'company_name' => 'No Verify Co',
            'name' => 'Owner',
            'email' => 'owner@noverify.test',
            'password' => 'SecurePass1!',
            'password_confirmation' => 'SecurePass1!',
        ])->assertRedirect(route('dashboard'));

        $user = User::withoutCompanyScope()->where('email', 'owner@noverify.test')->first();

        $this->assertNotNull($user);
        $this->assertTrue($user->hasVerifiedEmail());
        Notification::assertSentTo($user, \App\Notifications\WelcomeNotification::class);
        Notification::assertNotSentTo($user, AccountActivationNotification::class);
    }

    public function test_public_registration_requires_verification_when_enabled(): void
    {
        Notification::fake();

        app(PlatformSettingsService::class)->setMany([
            'registration_enabled' => true,
            'email_verification_required' => true,
        ]);

        $this->post(route('register'), [
            'company_name' => 'Verify Co',
            'name' => 'Owner',
            'email' => 'owner@verify.test',
            'password' => 'SecurePass1!',
            'password_confirmation' => 'SecurePass1!',
        ])->assertRedirect(route('verification.notice'));

        $user = User::withoutCompanyScope()->where('email', 'owner@verify.test')->first();

        $this->assertNotNull($user);
        $this->assertFalse($user->hasVerifiedEmail());
        Notification::assertSentTo($user, AccountActivationNotification::class);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('verification.notice'));
    }
}
