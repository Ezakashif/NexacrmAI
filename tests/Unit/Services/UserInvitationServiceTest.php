<?php

namespace Tests\Unit\Services;

use App\Models\Role;
use App\Models\User;
use App\Models\UserInvitation;
use App\Notifications\CompanyInvitationNotification;
use App\Services\UserInvitationService;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UserInvitationServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserInvitationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
        $this->service = app(UserInvitationService::class);
    }

    public function test_invite_creates_pending_invitation_and_sends_notification(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $salesRole = Role::query()
            ->where('company_id', $admin->company_id)
            ->where('slug', 'sales')
            ->firstOrFail();

        $invitation = $this->service->invite(
            $admin,
            'Jordan Lee',
            'jordan@example.com',
            [$salesRole->id],
        );

        $this->assertInstanceOf(UserInvitation::class, $invitation);
        $this->assertSame(UserInvitation::STATUS_PENDING, $invitation->status);
        $this->assertSame('jordan@example.com', $invitation->email);
        $this->assertSame($admin->company_id, $invitation->company_id);
        $this->assertTrue($invitation->isPending());

        $this->assertDatabaseHas('user_invitations', [
            'email' => 'jordan@example.com',
            'invited_by' => $admin->id,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'user.invitation_sent',
        ]);

        Notification::assertSentOnDemand(CompanyInvitationNotification::class);
    }

    public function test_invite_rejects_existing_user_email(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        User::factory()->create([
            'company_id' => $admin->company_id,
            'email' => 'existing@example.com',
        ]);
        $roleId = $admin->roles()->first()->id;

        try {
            $this->service->invite($admin, 'Existing User', 'existing@example.com', [$roleId]);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'A user with this email already exists.',
                $exception->errors()['email'][0],
            );
        }

        Notification::assertNothingSent();
    }

    public function test_invite_rejects_duplicate_pending_invitation(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $roleId = $admin->roles()->first()->id;

        UserInvitation::query()->create([
            'company_id' => $admin->company_id,
            'invited_by' => $admin->id,
            'name' => 'Pending User',
            'email' => 'pending@example.com',
            'role_ids' => [$roleId],
            'status' => UserInvitation::STATUS_PENDING,
            'expires_at' => now()->addDays(3),
        ]);

        try {
            $this->service->invite($admin, 'Another Name', 'pending@example.com', [$roleId]);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'An invitation is already pending for this email.',
                $exception->errors()['email'][0],
            );
        }
    }

    public function test_invite_rejects_non_admin_assigning_admin_role(): void
    {
        Notification::fake();

        $salesUser = User::factory()->create();
        $adminRole = Role::query()
            ->where('company_id', $salesUser->company_id)
            ->where('slug', 'admin')
            ->firstOrFail();

        try {
            $this->service->invite($salesUser, 'New Admin', 'newadmin@example.com', [$adminRole->id]);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'Only company admins can assign the Admin role.',
                $exception->errors()['roles'][0],
            );
        }
    }

    public function test_accept_creates_user_with_roles(): void
    {
        $admin = User::factory()->admin()->create();
        $salesRole = Role::query()
            ->where('company_id', $admin->company_id)
            ->where('slug', 'sales')
            ->firstOrFail();

        $invitation = UserInvitation::query()->create([
            'company_id' => $admin->company_id,
            'invited_by' => $admin->id,
            'name' => 'Accepted User',
            'email' => 'accepted@example.com',
            'role_ids' => [$salesRole->id],
            'status' => UserInvitation::STATUS_PENDING,
            'expires_at' => now()->addDays(7),
        ]);

        $user = $this->service->accept($invitation, ['password' => 'Secretpass1!']);

        $this->assertSame('accepted@example.com', $user->email);
        $this->assertSame($admin->company_id, $user->company_id);
        $this->assertTrue($user->hasRole('sales'));
        $this->assertTrue(Hash::check('Secretpass1!', $user->password));
        $this->assertNotNull($user->email_verified_at);

        $invitation->refresh();
        $this->assertSame(UserInvitation::STATUS_ACCEPTED, $invitation->status);
        $this->assertSame($user->id, $invitation->accepted_user_id);
        $this->assertNotNull($invitation->accepted_at);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'user.invitation_accepted',
        ]);
    }

    public function test_accept_rejects_expired_invitation(): void
    {
        $admin = User::factory()->admin()->create();
        $roleId = $admin->roles()->first()->id;

        $invitation = UserInvitation::query()->create([
            'company_id' => $admin->company_id,
            'invited_by' => $admin->id,
            'name' => 'Late User',
            'email' => 'late@example.com',
            'role_ids' => [$roleId],
            'status' => UserInvitation::STATUS_PENDING,
            'expires_at' => now()->subDay(),
        ]);

        try {
            $this->service->accept($invitation, ['password' => 'Secretpass1!']);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'This invitation is no longer valid.',
                $exception->errors()['token'][0],
            );
        }

        $this->assertSame(UserInvitation::STATUS_EXPIRED, $invitation->fresh()->status);
        $this->assertDatabaseMissing('users', ['email' => 'late@example.com']);
    }

    public function test_accept_rejects_already_accepted_invitation(): void
    {
        $admin = User::factory()->admin()->create();
        $roleId = $admin->roles()->first()->id;

        $invitation = UserInvitation::query()->create([
            'company_id' => $admin->company_id,
            'invited_by' => $admin->id,
            'name' => 'Done User',
            'email' => 'done@example.com',
            'role_ids' => [$roleId],
            'status' => UserInvitation::STATUS_ACCEPTED,
            'expires_at' => now()->addDay(),
            'accepted_at' => now(),
        ]);

        try {
            $this->service->accept($invitation, ['password' => 'Secretpass1!']);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'This invitation is no longer valid.',
                $exception->errors()['token'][0],
            );
        }
    }

    public function test_accept_rejects_when_user_email_already_exists(): void
    {
        $admin = User::factory()->admin()->create();
        $roleId = $admin->roles()->first()->id;

        User::factory()->create([
            'company_id' => $admin->company_id,
            'email' => 'taken@example.com',
        ]);

        $invitation = UserInvitation::query()->create([
            'company_id' => $admin->company_id,
            'invited_by' => $admin->id,
            'name' => 'Taken User',
            'email' => 'taken@example.com',
            'role_ids' => [$roleId],
            'status' => UserInvitation::STATUS_PENDING,
            'expires_at' => now()->addDay(),
        ]);

        try {
            $this->service->accept($invitation, ['password' => 'Secretpass1!']);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'A user with this email already exists.',
                $exception->errors()['email'][0],
            );
        }
    }
}
