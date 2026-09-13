<?php

namespace Tests\Feature\Auth;

use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\User;
use App\Services\Auth\LoginSecurityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertDontSee('name="company"', false);
        $response->assertSee('data-password-toggle', false);
        $response->assertSee('js/password-toggle.js', false);
        $response->assertSee('css/password-field.css', false);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create([
            'failed_login_attempts' => 2,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $user->refresh();
        $this->assertNotNull($user->last_login_at);
        $this->assertNotNull($user->last_login_ip);
        $this->assertSame(0, $user->failed_login_attempts);
        $this->assertNull($user->locked_until);

        $this->assertTrue(
            ActivityLog::withoutCompanyScope()
                ->where('user_id', $user->id)
                ->where('action', 'user.login')
                ->exists()
        );
    }

    public function test_super_admin_can_authenticate_with_email_only(): void
    {
        $superAdmin = User::factory()->superAdmin()->create([
            'email' => 'superadmin@example.com',
        ]);

        $this->post('/login', [
            'email' => 'superadmin@example.com',
            'password' => 'password',
        ])->assertRedirect(route('superadmin.dashboard', absolute: false));

        $this->assertAuthenticatedAs($superAdmin);
    }

    public function test_user_emails_must_be_globally_unique(): void
    {
        $companyA = Company::factory()->create(['slug' => 'acme']);
        $companyB = Company::factory()->create(['slug' => 'beta']);

        User::factory()->create([
            'company_id' => $companyA->id,
            'email' => 'shared@example.com',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        User::factory()->create([
            'company_id' => $companyB->id,
            'email' => 'shared@example.com',
        ]);
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertSame(1, $user->fresh()->failed_login_attempts);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'user.login_failed',
        ]);
    }

    public function test_inactive_user_gets_generic_failed_message(): void
    {
        $user = User::factory()->inactive()->create();

        $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect('/login')
            ->assertSessionHasErrors([
                'email' => trans('auth.failed'),
            ]);

        $this->assertGuest();
    }

    public function test_account_locks_after_repeated_failed_logins(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < LoginSecurityService::MAX_ATTEMPTS; $i++) {
            $this->from('/login')->post('/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);
        }

        $user->refresh();
        $this->assertSame(LoginSecurityService::MAX_ATTEMPTS, $user->failed_login_attempts);
        $this->assertNotNull($user->locked_until);
        $this->assertTrue($user->locked_until->isFuture());

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'user.locked_out',
        ]);

        $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertStringContainsString('Too many failed sign-in attempts', session('errors')->first('email'));
    }

    public function test_lockout_clears_after_expiry_and_allows_login(): void
    {
        $user = User::factory()->create([
            'failed_login_attempts' => LoginSecurityService::MAX_ATTEMPTS,
            'locked_until' => now()->subMinute(),
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
        $user->refresh();
        $this->assertSame(0, $user->failed_login_attempts);
        $this->assertNull($user->locked_until);
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect(route('login'));
    }

    public function test_logout_with_expired_session_redirects_to_login(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->post('/logout', ['_token' => 'stale-token']);

        $this->assertGuest();
        $response->assertRedirect(route('login'));
    }

    public function test_token_mismatch_redirects_to_login(): void
    {
        Route::get('/__test/csrf-expired', function () {
            throw new TokenMismatchException('CSRF token mismatch.');
        })->middleware('web');

        $response = $this->get('/__test/csrf-expired');

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status');
    }

    public function test_http_419_redirects_to_login(): void
    {
        Route::get('/__test/page-expired', function () {
            throw new HttpException(419, 'Page Expired');
        })->middleware('web');

        $response = $this->get('/__test/page-expired');

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status');
    }

    public function test_tenant_user_provider_retrieves_by_id_when_fail_closed(): void
    {
        config(['tenancy.fail_closed_without_context' => true]);

        $user = User::factory()->create();

        $provider = Auth::createUserProvider('users');

        $retrieved = $provider->retrieveById($user->id);

        $this->assertNotNull($retrieved);
        $this->assertTrue($user->is($retrieved));
    }
}
