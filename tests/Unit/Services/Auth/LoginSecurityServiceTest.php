<?php

namespace Tests\Unit\Services\Auth;

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\Auth\LoginSecurityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LoginSecurityServiceTest extends TestCase
{
    use RefreshDatabase;

    private LoginSecurityService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(LoginSecurityService::class);
    }

    public function test_is_locked_is_false_when_not_locked(): void
    {
        $user = User::factory()->create([
            'locked_until' => null,
        ]);

        $this->assertFalse($this->service->isLocked($user));
    }

    public function test_is_locked_is_true_when_locked_until_is_in_future(): void
    {
        $user = User::factory()->create([
            'locked_until' => now()->addMinutes(5),
        ]);

        $this->assertTrue($this->service->isLocked($user));
    }

    public function test_is_locked_is_false_when_locked_until_is_in_past(): void
    {
        $user = User::factory()->create([
            'locked_until' => now()->subMinute(),
        ]);

        $this->assertFalse($this->service->isLocked($user));
    }

    public function test_lockout_message_uses_singular_minute_when_one_minute_remaining(): void
    {
        $user = User::factory()->create([
            'locked_until' => now()->addSeconds(30),
        ]);

        $this->assertSame(
            'Too many failed sign-in attempts. Try again in 1 minute.',
            $this->service->lockoutMessage($user),
        );
    }

    public function test_lockout_message_uses_plural_minutes_when_multiple_minutes_remaining(): void
    {
        $user = User::factory()->create([
            'locked_until' => now()->addMinutes(10),
        ]);

        $message = $this->service->lockoutMessage($user);

        $this->assertStringContainsString('Try again in', $message);
        $this->assertStringContainsString('minutes', $message);
        $this->assertStringNotContainsString('1 minute.', $message);
    }

    public function test_assert_not_locked_throws_when_account_is_locked(): void
    {
        $user = User::factory()->create([
            'locked_until' => now()->addMinutes(LoginSecurityService::LOCKOUT_MINUTES),
        ]);

        try {
            $this->service->assertNotLocked($user);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                $this->service->lockoutMessage($user),
                $exception->errors()['email'][0],
            );
        }
    }

    public function test_assert_not_locked_clears_expired_lockout(): void
    {
        $user = User::factory()->create([
            'failed_login_attempts' => LoginSecurityService::MAX_ATTEMPTS,
            'locked_until' => now()->subMinute(),
        ]);

        $this->service->assertNotLocked($user);

        $user->refresh();
        $this->assertSame(0, $user->failed_login_attempts);
        $this->assertNull($user->locked_until);
    }

    public function test_assert_not_locked_passes_for_unlocked_user(): void
    {
        $user = User::factory()->create([
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ]);

        $this->service->assertNotLocked($user);

        $this->assertSame(0, $user->fresh()->failed_login_attempts);
    }

    public function test_record_failed_attempt_increments_attempts_without_locking_before_max(): void
    {
        $user = User::factory()->create([
            'failed_login_attempts' => 0,
        ]);
        $request = Request::create('/login', 'POST', server: ['HTTP_USER_AGENT' => 'PHPUnit']);

        $newlyLocked = $this->service->recordFailedAttempt($user, $request);

        $user->refresh();
        $this->assertFalse($newlyLocked);
        $this->assertSame(1, $user->failed_login_attempts);
        $this->assertNull($user->locked_until);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'user.login_failed',
        ]);
    }

    public function test_record_failed_attempt_locks_account_at_max_attempts(): void
    {
        $user = User::factory()->create([
            'failed_login_attempts' => LoginSecurityService::MAX_ATTEMPTS - 1,
        ]);
        $request = Request::create('/login', 'POST', server: ['HTTP_USER_AGENT' => 'PHPUnit']);

        $newlyLocked = $this->service->recordFailedAttempt($user, $request);

        $user->refresh();
        $this->assertTrue($newlyLocked);
        $this->assertSame(LoginSecurityService::MAX_ATTEMPTS, $user->failed_login_attempts);
        $this->assertNotNull($user->locked_until);
        $this->assertTrue($user->locked_until->isFuture());
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'user.locked_out',
        ]);
    }

    public function test_clear_failures_resets_counters_when_needed(): void
    {
        $user = User::factory()->create([
            'failed_login_attempts' => 3,
            'locked_until' => now()->addMinutes(5),
        ]);

        $this->service->clearFailures($user);

        $user->refresh();
        $this->assertSame(0, $user->failed_login_attempts);
        $this->assertNull($user->locked_until);
    }

    public function test_clear_failures_is_noop_when_already_clear(): void
    {
        $user = User::factory()->create([
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ]);

        $this->service->clearFailures($user);

        $user->refresh();
        $this->assertSame(0, $user->failed_login_attempts);
        $this->assertNull($user->locked_until);
    }

    public function test_log_unknown_email_failure_writes_activity_without_user(): void
    {
        $request = Request::create('/login', 'POST', server: ['HTTP_USER_AGENT' => 'PHPUnit']);

        $this->service->logUnknownEmailFailure($request, 'unknown@example.com');

        $log = ActivityLog::withoutCompanyScope()
            ->where('action', 'user.login_failed')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertNull($log->user_id);
        $this->assertSame('unknown@example.com', $log->properties['email'] ?? null);
        $this->assertTrue($log->properties['unknown_account'] ?? false);
    }
}
