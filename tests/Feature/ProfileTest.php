<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SuperAdmin\PlatformSettingsService;
use App\Notifications\AccountActivationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create([
            'last_login_at' => now()->subHour(),
            'last_login_ip' => '203.0.113.10',
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
        $response->assertSee('Active sessions', false);
        $response->assertSee('203.0.113.10', false);
        $response->assertSee('Phone', false);
        $response->assertSee('Timezone', false);
        $response->assertSee('Language', false);
    }

    public function test_profile_information_can_be_updated(): void
    {
        Notification::fake();
        app(PlatformSettingsService::class)->setMany(['email_verification_required' => true]);

        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'phone' => '+1 555 0100',
                'timezone' => 'America/New_York',
                'language' => 'en',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile')
            ->assertSessionHas('status', 'verification-link-sent');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertSame('+1 555 0100', $user->phone);
        $this->assertSame('America/New_York', $user->timezone);
        $this->assertSame('en', $user->language);
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, AccountActivationNotification::class);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
                'phone' => null,
                'timezone' => null,
                'language' => null,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }

    public function test_user_can_revoke_another_session(): void
    {
        $user = User::factory()->create();

        DB::table('sessions')->insert([
            'id' => 'other-session-id',
            'user_id' => $user->id,
            'ip_address' => '198.51.100.20',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0',
            'payload' => 'test',
            'last_activity' => now()->subMinutes(5)->timestamp,
        ]);

        $this->actingAs($user)
            ->from('/profile')
            ->delete(route('profile.sessions.destroy', 'other-session-id'))
            ->assertRedirect('/profile')
            ->assertSessionHas('status', 'session-revoked');

        $this->assertDatabaseMissing('sessions', ['id' => 'other-session-id']);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'session.revoked',
        ]);
    }

    public function test_user_can_revoke_other_sessions(): void
    {
        $user = User::factory()->create();

        DB::table('sessions')->insert([
            [
                'id' => 'other-a',
                'user_id' => $user->id,
                'ip_address' => '198.51.100.21',
                'user_agent' => 'Firefox',
                'payload' => 'a',
                'last_activity' => now()->timestamp,
            ],
            [
                'id' => 'other-b',
                'user_id' => $user->id,
                'ip_address' => '198.51.100.22',
                'user_agent' => 'Safari',
                'payload' => 'b',
                'last_activity' => now()->timestamp,
            ],
        ]);

        $this->actingAs($user)
            ->from('/profile')
            ->delete(route('profile.sessions.destroy-others'))
            ->assertRedirect('/profile')
            ->assertSessionHas('status', 'sessions-revoked');

        $this->assertDatabaseMissing('sessions', ['id' => 'other-a']);
        $this->assertDatabaseMissing('sessions', ['id' => 'other-b']);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'session.revoked_others',
        ]);
    }

    public function test_session_manager_preserves_excepted_session_and_blocks_missing(): void
    {
        $user = User::factory()->create();

        DB::table('sessions')->insert([
            [
                'id' => 'keep-me',
                'user_id' => $user->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'PHPUnit',
                'payload' => 'current',
                'last_activity' => now()->timestamp,
            ],
            [
                'id' => 'drop-me',
                'user_id' => $user->id,
                'ip_address' => '198.51.100.30',
                'user_agent' => 'Chrome',
                'payload' => 'other',
                'last_activity' => now()->timestamp,
            ],
        ]);

        $manager = app(\App\Services\Auth\SessionManager::class);

        $this->assertSame(1, $manager->destroyOtherSessions($user, 'keep-me'));
        $this->assertDatabaseHas('sessions', ['id' => 'keep-me']);
        $this->assertDatabaseMissing('sessions', ['id' => 'drop-me']);
        $this->assertFalse($manager->destroyForUser($user, 'missing'));
        $this->assertSame('Chrome on Unknown OS', $manager->deviceLabel('Chrome/120.0'));
    }
}
