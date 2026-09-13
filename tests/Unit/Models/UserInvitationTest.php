<?php

namespace Tests\Unit\Models;

use App\Models\Company;
use App\Models\User;
use App\Models\UserInvitation;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserInvitationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    public function test_generates_unique_token_on_create(): void
    {
        $invitation = $this->createInvitation();

        $this->assertNotEmpty($invitation->token);
        $this->assertSame(64, strlen($invitation->token));
    }

    public function test_preserves_explicit_token(): void
    {
        $invitation = $this->createInvitation(['token' => 'custom-token-value']);

        $this->assertSame('custom-token-value', $invitation->token);
    }

    public function test_is_pending_when_status_pending_and_not_expired(): void
    {
        $invitation = $this->createInvitation([
            'status' => UserInvitation::STATUS_PENDING,
            'expires_at' => now()->addDay(),
        ]);

        $this->assertTrue($invitation->isPending());
    }

    public function test_is_not_pending_when_expired_by_date(): void
    {
        $invitation = $this->createInvitation([
            'status' => UserInvitation::STATUS_PENDING,
            'expires_at' => now()->subMinute(),
        ]);

        $this->assertFalse($invitation->isPending());
    }

    public function test_is_not_pending_when_status_is_not_pending(): void
    {
        $invitation = $this->createInvitation([
            'status' => UserInvitation::STATUS_ACCEPTED,
            'expires_at' => now()->addDay(),
        ]);

        $this->assertFalse($invitation->isPending());
    }

    public function test_mark_expired_if_needed_updates_pending_past_due_invitation(): void
    {
        $invitation = $this->createInvitation([
            'status' => UserInvitation::STATUS_PENDING,
            'expires_at' => now()->subHour(),
        ]);

        $invitation->markExpiredIfNeeded();

        $this->assertSame(UserInvitation::STATUS_EXPIRED, $invitation->fresh()->status);
    }

    public function test_mark_expired_if_needed_does_not_touch_future_pending_invitation(): void
    {
        $invitation = $this->createInvitation([
            'status' => UserInvitation::STATUS_PENDING,
            'expires_at' => now()->addDay(),
        ]);

        $invitation->markExpiredIfNeeded();

        $this->assertSame(UserInvitation::STATUS_PENDING, $invitation->fresh()->status);
    }

    public function test_scope_pending_filters_by_status_only(): void
    {
        $pending = $this->createInvitation(['status' => UserInvitation::STATUS_PENDING]);
        $this->createInvitation(['status' => UserInvitation::STATUS_ACCEPTED, 'email' => 'accepted@example.com']);

        $ids = UserInvitation::query()->pending()->pluck('id');

        $this->assertTrue($ids->contains($pending->id));
        $this->assertSame(1, $ids->count());
    }

    public function test_relationships_resolve(): void
    {
        $admin = User::factory()->admin()->create();
        $invitation = $this->createInvitation(['invited_by' => $admin->id]);

        $acceptedUser = User::factory()->create(['email' => 'joined@example.com']);
        $invitation->forceFill([
            'status' => UserInvitation::STATUS_ACCEPTED,
            'accepted_user_id' => $acceptedUser->id,
            'accepted_at' => now(),
        ])->save();

        $invitation->load(['company', 'inviter', 'acceptedUser']);

        $this->assertSame($admin->company_id, $invitation->company->id);
        $this->assertSame($admin->id, $invitation->inviter->id);
        $this->assertSame($acceptedUser->id, $invitation->acceptedUser->id);
    }

    public function test_role_ids_are_cast_to_array(): void
    {
        $admin = User::factory()->admin()->create();
        $roleId = $admin->roles()->first()->id;

        $invitation = $this->createInvitation(['role_ids' => [$roleId]]);

        $this->assertIsArray($invitation->role_ids);
        $this->assertSame([$roleId], $invitation->role_ids);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createInvitation(array $overrides = []): UserInvitation
    {
        $admin = User::factory()->admin()->create();
        $roleId = $admin->roles()->first()->id;

        return UserInvitation::query()->create(array_merge([
            'company_id' => $admin->company_id,
            'invited_by' => $admin->id,
            'name' => 'Invitee User',
            'email' => 'invitee@example.com',
            'role_ids' => [$roleId],
            'status' => UserInvitation::STATUS_PENDING,
            'expires_at' => now()->addDays(7),
        ], $overrides));
    }
}
