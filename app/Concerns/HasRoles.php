<?php

namespace App\Concerns;

use App\Models\Lead;
use App\Models\Task;
use App\Models\Role;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

trait HasRoles
{
    protected ?Collection $cachedPermissionSlugs = null;

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    public function hasRole(string $slug): bool
    {
        if ($this->relationLoaded('roles')) {
            return $this->roles->contains('slug', $slug);
        }

        return $this->roles()->where('slug', $slug)->exists();
    }

    public function hasAnyRole(array $slugs): bool
    {
        if ($this->relationLoaded('roles')) {
            return $this->roles->pluck('slug')->intersect($slugs)->isNotEmpty();
        }

        return $this->roles()->whereIn('slug', $slugs)->exists();
    }

    public function permissionSlugs(): Collection
    {
        if ($this->cachedPermissionSlugs !== null) {
            return $this->cachedPermissionSlugs;
        }

        $this->cachedPermissionSlugs = $this->roles()
            ->with('permissions:id,slug')
            ->get()
            ->flatMap(fn (Role $role) => $role->permissions->pluck('slug'))
            ->unique()
            ->values();

        return $this->cachedPermissionSlugs;
    }

    public function hasPermission(string $slug): bool
    {
        return $this->permissionSlugs()->contains($slug);
    }

    public function hasAnyPermission(array $slugs): bool
    {
        return $this->permissionSlugs()->intersect($slugs)->isNotEmpty();
    }

    /**
     * Reports hub is available with an explicit reports permission,
     * or when the user can already view CRM lead/task/customer data.
     */
    public function canAccessReports(): bool
    {
        return $this->hasPermission('view.reports')
            || $this->hasAnyPermission(['view.leads', 'view.tasks', 'view.customers']);
    }

    public function canViewAllTasks(): bool
    {
        return $this->hasPermission('view_all.tasks');
    }

    public function canAssignTasks(): bool
    {
        return $this->hasPermission('assign.tasks');
    }

    public function ownsTask(Task $task): bool
    {
        return $task->assigned_to === $this->id;
    }

    public function canManageAnyTask(): bool
    {
        return $this->canViewAllTasks() && $this->canAssignTasks();
    }

    public function canViewAllLeads(): bool
    {
        return $this->hasPermission('view_all.leads');
    }

    public function canAssignLeads(): bool
    {
        return $this->hasPermission('assign.leads');
    }

    public function ownsLead(Lead $lead): bool
    {
        return $lead->assigned_to === $this->id;
    }

    public function canManageAnyLead(): bool
    {
        return $this->canViewAllLeads() && $this->canAssignLeads();
    }

    /**
     * @param  array<int|string>  $roleIds
     */
    public function syncRoles(array $roleIds): void
    {
        if ($this->company_id !== null) {
            $roleIds = Role::withoutCompanyScope()
                ->where('company_id', $this->company_id)
                ->whereIn('id', $roleIds)
                ->pluck('id')
                ->all();
        }

        $this->roles()->sync($roleIds);
        $this->cachedPermissionSlugs = null;
        $this->syncLegacyRoleColumn();
    }

    /**
     * @param  array<int, string>  $slugs
     */
    public function syncRolesBySlug(array $slugs): void
    {
        $query = Role::withoutCompanyScope()->whereIn('slug', $slugs);

        if ($this->company_id !== null) {
            $query->where('company_id', $this->company_id);
        }

        $roleIds = $query->pluck('id')->all();

        $this->syncRoles($roleIds);
    }

    public function syncRolesFromLegacyColumn(): void
    {
        if ($this->isSuperAdmin()) {
            return;
        }

        $slug = match ($this->role) {
            'admin' => 'admin',
            default => 'sales',
        };

        $query = Role::withoutCompanyScope()->where('slug', $slug);

        if ($this->company_id !== null) {
            $query->where('company_id', $this->company_id);
        }

        $role = $query->first();

        if ($role) {
            $this->roles()->syncWithoutDetaching([$role->id]);
        }
    }

    public function syncLegacyRoleColumn(): void
    {
        $slugs = $this->roles()->pluck('slug')->all();

        $this->role = match (true) {
            in_array('admin', $slugs, true) => 'admin',
            default => 'user',
        };

        $this->saveQuietly();
    }

    public function roleNames(): string
    {
        if ($this->relationLoaded('roles')) {
            return $this->roles->pluck('name')->join(', ');
        }

        return $this->roles()->pluck('name')->join(', ');
    }
}
