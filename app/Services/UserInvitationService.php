<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use App\Models\UserInvitation;
use App\Notifications\CompanyInvitationNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class UserInvitationService
{
    public function __construct(
        private readonly PlanLimitService $planLimits,
        private readonly RoleAssignmentGuard $roleAssignmentGuard,
    ) {}

    /**
     * @param  list<int>  $roleIds
     */
    public function invite(User $actor, string $name, string $email, array $roleIds): UserInvitation
    {
        $this->roleAssignmentGuard->assertCanAssignRoles($actor, $roleIds);
        $this->planLimits->assertCanAddUser($actor->company);

        if (User::withoutCompanyScope()->where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'A user with this email already exists.',
            ]);
        }

        $pending = UserInvitation::query()
            ->where('company_id', $actor->company_id)
            ->where('email', $email)
            ->pending()
            ->where('expires_at', '>', now())
            ->exists();

        if ($pending) {
            throw ValidationException::withMessages([
                'email' => 'An invitation is already pending for this email.',
            ]);
        }

        $roles = Role::query()
            ->where('company_id', $actor->company_id)
            ->whereIn('id', $roleIds)
            ->orderBy('name')
            ->get();

        $invitation = DB::transaction(function () use ($actor, $name, $email, $roleIds) {
            $invitation = UserInvitation::query()->create([
                'company_id' => $actor->company_id,
                'invited_by' => $actor->id,
                'name' => $name,
                'email' => $email,
                'role_ids' => array_values($roleIds),
                'status' => UserInvitation::STATUS_PENDING,
                'expires_at' => now()->addDays(7),
            ]);

            ActivityLogger::log('user.invitation_sent', $invitation, [
                'email' => $email,
                'name' => $name,
                'role_ids' => $roleIds,
            ]);

            return $invitation;
        });

        Notification::route('mail', $invitation->email)
            ->notify(new CompanyInvitationNotification(
                $invitation,
                $roles->pluck('name')->join(', ') ?: 'team member',
            ));

        return $invitation;
    }

    /**
     * @param  array{password: string}  $credentials
     */
    public function accept(UserInvitation $invitation, array $credentials): User
    {
        $invitation->markExpiredIfNeeded();
        $invitation->refresh();

        if (! $invitation->isPending()) {
            throw ValidationException::withMessages([
                'token' => 'This invitation is no longer valid.',
            ]);
        }

        if (User::withoutCompanyScope()->where('email', $invitation->email)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'A user with this email already exists.',
            ]);
        }

        return DB::transaction(function () use ($invitation, $credentials) {
            $locked = UserInvitation::query()->lockForUpdate()->findOrFail($invitation->id);
            $locked->markExpiredIfNeeded();

            if (! $locked->isPending()) {
                throw ValidationException::withMessages([
                    'token' => 'This invitation is no longer valid.',
                ]);
            }

            $company = Company::query()->findOrFail($locked->company_id);

            $user = new User;
            $user->forceFill([
                'name' => $locked->name,
                'email' => $locked->email,
                'password' => $credentials['password'],
                'role' => 'user',
                'status' => 'active',
                'email_verified_at' => now(),
                'is_super_admin' => false,
            ]);
            $user->company_id = $company->id;
            $user->save();
            $user->syncRoles($locked->role_ids ?? []);

            $locked->forceFill([
                'status' => UserInvitation::STATUS_ACCEPTED,
                'accepted_at' => now(),
                'accepted_user_id' => $user->id,
            ])->save();

            ActivityLogger::log('user.invitation_accepted', $locked, [
                'email' => $user->email,
                'user_id' => $user->id,
            ], $user->id);

            return $user;
        });
    }
}
