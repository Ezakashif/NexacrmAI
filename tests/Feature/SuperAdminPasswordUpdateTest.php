<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SuperAdminPasswordUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    public function test_guest_is_redirected_to_login_from_account_page(): void
    {
        $this->get(route('superadmin.account.edit'))
            ->assertRedirect(route('login'));

        $this->put(route('superadmin.password.update'), [
            'current_password' => 'password',
            'password' => 'SecurePass1!',
            'password_confirmation' => 'SecurePass1!',
        ])->assertRedirect(route('login'));
    }

    public function test_tenant_admin_cannot_view_or_update_super_admin_password(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('superadmin.account.edit'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->put(route('superadmin.password.update'), [
                'current_password' => 'password',
                'password' => 'SecurePass1!',
                'password_confirmation' => 'SecurePass1!',
            ])
            ->assertForbidden();

        $admin->refresh();

        $this->assertTrue(Hash::check('password', $admin->password));
    }

    public function test_super_admin_can_view_account_page(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->get(route('superadmin.account.edit'))
            ->assertOk()
            ->assertSee('Change password', false)
            ->assertSee('Current password', false)
            ->assertSee('New password', false)
            ->assertSee('name="current_password"', false)
            ->assertSee('name="password"', false)
            ->assertSee('name="password_confirmation"', false)
            ->assertSee(route('superadmin.password.update'), false)
            ->assertDontSee('a number', false);
    }

    public function test_super_admin_can_update_password(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->get(route('superadmin.account.edit'))
            ->assertOk();

        $currentId = session()->getId();

        DB::table('sessions')->insert([
            [
                'id' => $currentId,
                'user_id' => $superAdmin->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'PHPUnit',
                'payload' => 'current',
                'last_activity' => now()->timestamp,
            ],
            [
                'id' => 'stale-session',
                'user_id' => $superAdmin->id,
                'ip_address' => '203.0.113.50',
                'user_agent' => 'Old Browser',
                'payload' => 'stale',
                'last_activity' => now()->subDay()->timestamp,
            ],
        ]);

        $oldRemember = $superAdmin->remember_token;

        $response = $this
            ->actingAs($superAdmin)
            ->from(route('superadmin.account.edit'))
            ->put(route('superadmin.password.update'), [
                'current_password' => 'password',
                'password' => 'SecurePass1!',
                'password_confirmation' => 'SecurePass1!',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('superadmin.account.edit'))
            ->assertSessionHas('status', 'password-updated');

        $superAdmin->refresh();

        $this->assertTrue(Hash::check('SecurePass1!', $superAdmin->password));
        $this->assertNotSame($oldRemember, $superAdmin->remember_token);
        $this->assertDatabaseMissing('sessions', ['id' => 'stale-session']);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $superAdmin->id,
            'action' => 'password.updated',
        ]);
    }

    public function test_correct_password_must_be_provided_to_update_super_admin_password(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->from(route('superadmin.account.edit'))
            ->put(route('superadmin.password.update'), [
                'current_password' => 'wrong-password',
                'password' => 'SecurePass1!',
                'password_confirmation' => 'SecurePass1!',
            ])
            ->assertSessionHasErrorsIn('updatePassword', 'current_password')
            ->assertRedirect(route('superadmin.account.edit'));

        $superAdmin->refresh();

        $this->assertTrue(Hash::check('password', $superAdmin->password));
    }

    public function test_new_password_must_meet_platform_rules(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->from(route('superadmin.account.edit'))
            ->put(route('superadmin.password.update'), [
                'current_password' => 'password',
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
            ->assertSessionHasErrorsIn('updatePassword', 'password')
            ->assertRedirect(route('superadmin.account.edit'));

        $superAdmin->refresh();

        $this->assertTrue(Hash::check('password', $superAdmin->password));
    }

    public function test_super_admin_is_still_redirected_away_from_tenant_profile_and_password(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->get('/profile')
            ->assertRedirect(route('superadmin.dashboard'));

        $this->actingAs($superAdmin)
            ->from('/profile')
            ->put('/password', [
                'current_password' => 'password',
                'password' => 'SecurePass1!',
                'password_confirmation' => 'SecurePass1!',
            ])
            ->assertRedirect(route('superadmin.dashboard'));

        $superAdmin->refresh();

        $this->assertTrue(Hash::check('password', $superAdmin->password));
    }
}
