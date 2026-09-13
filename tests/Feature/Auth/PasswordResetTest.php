<?php

namespace Tests\Feature\Auth;

use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Models\ActivityLog;
use App\Models\User;
use App\Notifications\PasswordResetNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
        $response->assertDontSee('name="company"', false);
        $response->assertSee('Reset links expire after', false);
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $response = $this->post('/forgot-password', [
            'email' => $user->email,
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', PasswordResetLinkController::REQUEST_STATUS_MESSAGE);

        Notification::assertSentTo($user, PasswordResetNotification::class);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'password.reset_requested',
        ]);
    }

    public function test_unknown_email_does_not_reveal_account_existence(): void
    {
        Notification::fake();

        $response = $this->from('/forgot-password')->post('/forgot-password', [
            'email' => 'missing@example.com',
        ]);

        $response
            ->assertRedirect('/forgot-password')
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', PasswordResetLinkController::REQUEST_STATUS_MESSAGE);

        Notification::assertNothingSent();
        $this->assertSame(0, ActivityLog::withoutCompanyScope()->count());
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', [
            'email' => $user->email,
        ]);

        Notification::assertSentTo($user, PasswordResetNotification::class, function ($notification) {
            $response = $this->get('/reset-password/'.$notification->token);

            $response->assertStatus(200);
            $response->assertSee('Choose a new password', false);

            return true;
        });
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
        ]);

        DB::table('sessions')->insert([
            'id' => 'old-session-'.$user->id,
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => 'test',
            'last_activity' => now()->timestamp,
        ]);

        $this->post('/forgot-password', [
            'email' => $user->email,
        ]);

        Notification::assertSentTo($user, PasswordResetNotification::class, function ($notification) use ($user) {
            $oldRememberToken = $user->fresh()->remember_token;

            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'SecurePass1!',
                'password_confirmation' => 'SecurePass1!',
            ]);

            $response
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('login'))
                ->assertSessionHas('status', NewPasswordController::RESET_SUCCESS_MESSAGE);

            $user->refresh();

            $this->assertTrue(Hash::check('SecurePass1!', $user->password));
            $this->assertNotSame($oldRememberToken, $user->remember_token);
            $this->assertGuest();

            $this->assertDatabaseMissing('sessions', [
                'user_id' => $user->id,
            ]);

            $this->assertDatabaseHas('activity_logs', [
                'user_id' => $user->id,
                'action' => 'password.reset',
            ]);

            return true;
        });
    }

    public function test_invalid_reset_token_shows_generic_failure_message(): void
    {
        $user = User::factory()->create();

        $response = $this->from('/reset-password/invalid-token')->post('/reset-password', [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'SecurePass1!',
            'password_confirmation' => 'SecurePass1!',
        ]);

        $response
            ->assertRedirect('/reset-password/invalid-token')
            ->assertSessionHasErrors([
                'email' => NewPasswordController::RESET_FAILURE_MESSAGE,
            ]);

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_password_confirmation_is_required_on_reset(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', [
            'email' => $user->email,
        ]);

        Notification::assertSentTo($user, PasswordResetNotification::class, function ($notification) use ($user) {
            $response = $this->from('/reset-password/'.$notification->token)->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'new-password',
                'password_confirmation' => 'different-password',
            ]);

            $response->assertSessionHasErrors('password');

            return true;
        });
    }
}
