<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_can_be_updated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/profile')->assertOk();
        $currentId = session()->getId();

        DB::table('sessions')->insert([
            [
                'id' => $currentId,
                'user_id' => $user->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'PHPUnit',
                'payload' => 'current',
                'last_activity' => now()->timestamp,
            ],
            [
                'id' => 'stale-session',
                'user_id' => $user->id,
                'ip_address' => '203.0.113.50',
                'user_agent' => 'Old Browser',
                'payload' => 'stale',
                'last_activity' => now()->subDay()->timestamp,
            ],
        ]);

        $oldRemember = $user->remember_token;

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'current_password' => 'password',
                'password' => 'SecurePass1!',
                'password_confirmation' => 'SecurePass1!',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile')
            ->assertSessionHas('status', 'password-updated');

        $user->refresh();

        $this->assertTrue(Hash::check('SecurePass1!', $user->password));
        $this->assertNotSame($oldRemember, $user->remember_token);
        $this->assertDatabaseMissing('sessions', ['id' => 'stale-session']);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'password.updated',
        ]);
    }

    public function test_correct_password_must_be_provided_to_update_password(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'current_password' => 'wrong-password',
                'password' => 'SecurePass1!',
                'password_confirmation' => 'SecurePass1!',
            ]);

        $response
            ->assertSessionHasErrorsIn('updatePassword', 'current_password')
            ->assertRedirect('/profile');
    }
}
