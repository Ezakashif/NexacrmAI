<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class RoleAssignmentGuard
{
    /**
     * Ensure the actor may assign the given role IDs (blocks non-admins granting Admin).
     *
     * @param  array<int|string>  $roleIds
     *
     * @throws ValidationException
     */
    public function assertCanAssignRoles(User $actor, array $roleIds): void
    {
        if ($actor->isAdmin()) {
            return;
        }

        $adminRoleId = Role::query()
            ->where('slug', 'admin')
            ->where('company_id', $actor->company_id)
            ->value('id');

        if ($adminRoleId === null) {
            return;
        }

        $normalized = array_map('intval', $roleIds);

        if (in_array((int) $adminRoleId, $normalized, true)) {
            throw ValidationException::withMessages([
                'roles' => 'Only company admins can assign the Admin role.',
            ]);
        }
    }

    /**
     * Filter permission IDs to those the actor is allowed to grant.
     * Admins may grant any permission; others may only grant permissions they hold.
     *
     * @param  array<int|string>|null  $permissionIds
     * @return list<int>
     *
     * @throws ValidationException
     */
    public function filterAssignablePermissions(User $actor, ?array $permissionIds): array
    {
        $requested = collect($permissionIds ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($requested->isEmpty()) {
            return [];
        }

        if ($actor->isAdmin()) {
            return $requested->all();
        }

        $ownedPermissionIds = $this->actorPermissionIds($actor);

        $forbidden = $requested->diff($ownedPermissionIds);

        if ($forbidden->isNotEmpty()) {
            throw ValidationException::withMessages([
                'permissions' => 'You cannot grant permissions you do not have.',
            ]);
        }

        return $requested->all();
    }

    /**
     * @return Collection<int, int>
     */
    private function actorPermissionIds(User $actor): Collection
    {
        $slugs = $actor->permissionSlugs();

        if ($slugs->isEmpty()) {
            return collect();
        }

        return Permission::query()
            ->whereIn('slug', $slugs->all())
            ->pluck('id');
    }
}
