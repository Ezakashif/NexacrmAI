<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Support\Facades\Schema;

class RbacRoleSynchronizer
{
    /**
     * Ensure default roles match the registry for one or all companies:
     * - only Administrator is a system role
     * - Manager is removed
     * - Sales is demoted to a custom/default role
     *
     * Falls back to the pre-tenancy slug-only sync when the companies schema
     * is not available yet (historical migrations run before Phase 1A).
     */
    public function syncDefaultRoles(?int $companyId = null): void
    {
        if (! $this->tenancyReady()) {
            $this->syncDefaultRolesWithoutTenancy();

            return;
        }

        $companyIds = $companyId !== null
            ? collect([$companyId])
            : Company::query()->orderBy('id')->pluck('id');

        if ($companyIds->isEmpty()) {
            $companyIds = collect([$this->ensureDefaultCompany()->id]);
        }

        foreach ($companyIds as $id) {
            $this->syncDefaultRolesForCompany((int) $id);
        }
    }

    public function syncDefaultRolesForCompany(Company|int $company): void
    {
        if (! $this->tenancyReady()) {
            $this->syncDefaultRolesWithoutTenancy();

            return;
        }

        $companyId = $company instanceof Company ? (int) $company->id : $company;

        $this->removeManagerRoleForCompany($companyId);

        $permissionsBySlug = Permission::query()->pluck('id', 'slug');

        foreach (RbacSeeder::ROLES as $slug => $attributes) {
            $role = Role::withoutCompanyScope()->updateOrCreate(
                [
                    'company_id' => $companyId,
                    'slug' => $slug,
                ],
                $attributes,
            );

            $this->syncRolePermissions($role, RbacSeeder::ROLE_PERMISSIONS[$slug], $permissionsBySlug);
        }

        Role::withoutCompanyScope()
            ->where('company_id', $companyId)
            ->where('slug', '!=', 'admin')
            ->where('is_system', true)
            ->update(['is_system' => false]);
    }

    public function removeManagerRole(): void
    {
        if (! $this->tenancyReady()) {
            $this->removeManagerRoleWithoutTenancy();

            return;
        }

        Company::query()
            ->orderBy('id')
            ->pluck('id')
            ->each(fn ($companyId) => $this->removeManagerRoleForCompany((int) $companyId));
    }

    public function removeManagerRoleForCompany(int $companyId): void
    {
        $managerRole = Role::withoutCompanyScope()
            ->where('company_id', $companyId)
            ->where('slug', 'manager')
            ->first();

        if (! $managerRole) {
            return;
        }

        $salesRole = Role::withoutCompanyScope()->updateOrCreate(
            [
                'company_id' => $companyId,
                'slug' => 'sales',
            ],
            RbacSeeder::ROLES['sales'],
        );

        $this->reassignManagerUsers($managerRole, $salesRole);

        $managerRole->permissions()->detach();
        $managerRole->users()->detach();
        $managerRole->delete();
    }

    /**
     * Pre-tenancy sync used by migrations that run before companies exist.
     */
    private function syncDefaultRolesWithoutTenancy(): void
    {
        $this->removeManagerRoleWithoutTenancy();

        $permissionsBySlug = Permission::query()->pluck('id', 'slug');

        foreach (RbacSeeder::ROLES as $slug => $attributes) {
            // Ignore CompanyScope so historical migrations can upsert by slug
            // before tenant rows exist (avoids duplicate slug inserts).
            $role = Role::withoutCompanyScope()->updateOrCreate(
                ['slug' => $slug],
                $attributes,
            );

            $this->syncRolePermissions($role, RbacSeeder::ROLE_PERMISSIONS[$slug], $permissionsBySlug);
        }

        Role::withoutCompanyScope()
            ->where('slug', '!=', 'admin')
            ->where('is_system', true)
            ->update(['is_system' => false]);
    }

    private function removeManagerRoleWithoutTenancy(): void
    {
        $managerRole = Role::withoutCompanyScope()->where('slug', 'manager')->first();

        if (! $managerRole) {
            return;
        }

        $salesRole = Role::withoutCompanyScope()->updateOrCreate(
            ['slug' => 'sales'],
            RbacSeeder::ROLES['sales'],
        );

        $this->reassignManagerUsers($managerRole, $salesRole);

        $managerRole->permissions()->detach();
        $managerRole->users()->detach();
        $managerRole->delete();
    }

    private function reassignManagerUsers(Role $managerRole, Role $salesRole): void
    {
        $managerUserIds = $managerRole->users()->pluck('users.id');

        foreach ($managerUserIds as $userId) {
            $user = $this->tenancyReady()
                ? User::withoutCompanyScope()->find($userId)
                : User::query()->find($userId);

            if (! $user) {
                continue;
            }

            $roleIds = $user->roles()
                ->where('roles.slug', '!=', 'manager')
                ->pluck('roles.id')
                ->all();

            if (! in_array($salesRole->id, $roleIds, true) && ! $user->hasRole('admin')) {
                $roleIds[] = $salesRole->id;
            }

            $user->syncRoles($roleIds);
        }
    }

    /**
     * @param  list<string>|*  $permissionSlugs
     * @param  \Illuminate\Support\Collection<string, int>  $permissionsBySlug
     */
    private function syncRolePermissions(Role $role, array|string $permissionSlugs, $permissionsBySlug): void
    {
        if ($permissionSlugs === '*') {
            $role->permissions()->sync($permissionsBySlug->values()->all());

            return;
        }

        $permissionIds = collect($permissionSlugs)
            ->filter(fn (string $permissionSlug) => $permissionsBySlug->has($permissionSlug))
            ->map(fn (string $permissionSlug) => $permissionsBySlug[$permissionSlug])
            ->all();

        $role->permissions()->sync($permissionIds);
    }

    private function ensureDefaultCompany(): Company
    {
        return Company::query()->firstOrCreate(
            ['slug' => Company::DEFAULT_SLUG],
            [
                'name' => 'Default Company',
                'status' => Company::STATUS_ACTIVE,
            ],
        );
    }

    private function tenancyReady(): bool
    {
        return Schema::hasTable('companies')
            && Schema::hasColumn('roles', 'company_id');
    }
}
