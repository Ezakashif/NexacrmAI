<?php

namespace App\Services;

use App\Models\Permission;
use Illuminate\Support\Collection;

class PermissionRegistry
{
    /**
     * Maps legacy slugs to the new {action}.{module} format.
     *
     * @return array<string, string>
     */
    public function legacySlugMap(): array
    {
        return [
            'customers.view' => 'view.customers',
            'customers.create' => 'create.customers',
            'customers.update' => 'update.customers',
            'customers.delete' => 'delete.customers',
            'leads.view' => 'view.leads',
            'leads.create' => 'create.leads',
            'leads.update' => 'update.leads',
            'leads.delete' => 'delete.leads',
            'leads.convert' => 'convert.leads',
            'leads.activities.create' => 'log.leads',
            'tasks.view' => 'view.tasks',
            'tasks.create' => 'create.tasks',
            'tasks.update' => 'update.tasks',
            'tasks.delete' => 'delete.tasks',
            'tasks.assign' => 'assign.tasks',
            'tasks.change_status' => 'change_status.tasks',
            'users.manage' => 'view.users',
            'roles.manage' => 'view.roles',
            'permissions.manage' => 'view.roles',
            'activity-logs.view' => 'view.activity_logs',
            'demo.website-lead' => 'website_lead.demo',
        ];
    }

    /**
     * @return array<string, array{label: string, actions: array<string, string>}>
     */
    public function modules(): array
    {
        return config('permissions.modules', []);
    }

    public function slug(string $action, string $module): string
    {
        return "{$action}.{$module}";
    }

    /**
     * @return Collection<int, string>
     */
    public function allSlugs(): Collection
    {
        return collect($this->modules())
            ->flatMap(fn (array $module, string $moduleKey) => collect($module['actions'])
                ->keys()
                ->map(fn (string $action) => $this->slug($action, $moduleKey)));
    }

    /**
     * Sync permissions from config. Creates missing permissions and removes stale ones.
     */
    public function sync(): void
    {
        $validSlugs = $this->allSlugs();

        foreach ($this->modules() as $moduleKey => $module) {
            foreach ($module['actions'] as $action => $actionLabel) {
                $slug = $this->slug($action, $moduleKey);

                Permission::query()->updateOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => "{$actionLabel} {$module['label']}",
                        'group' => $moduleKey,
                    ],
                );
            }
        }

        Permission::query()
            ->whereNotIn('slug', $validSlugs->all())
            ->each(function (Permission $permission) {
                $permission->roles()->detach();
                $permission->delete();
            });
    }

    /**
     * Remap role permissions from legacy slugs to the new format.
     */
    public function migrateLegacyRolePermissions(): void
    {
        $this->sync();

        $slugMap = $this->legacySlugMap();
        $permissionsBySlug = Permission::query()->pluck('id', 'slug');

        $expandedMap = [
            'users.manage' => ['view.users', 'create.users', 'update.users', 'delete.users'],
            'roles.manage' => ['view.roles', 'create.roles', 'update.roles', 'delete.roles'],
            'permissions.manage' => ['view.roles', 'create.roles', 'update.roles', 'delete.roles'],
        ];

        foreach (\App\Models\Role::withoutCompanyScope()->with('permissions')->get() as $role) {
            $newSlugs = collect();

            foreach ($role->permissions as $permission) {
                if (isset($expandedMap[$permission->slug])) {
                    $newSlugs = $newSlugs->merge($expandedMap[$permission->slug]);
                } elseif (isset($slugMap[$permission->slug])) {
                    $newSlugs->push($slugMap[$permission->slug]);
                } else {
                    $newSlugs->push($permission->slug);
                }
            }

            $permissionIds = $newSlugs
                ->unique()
                ->filter(fn (string $slug) => $permissionsBySlug->has($slug))
                ->map(fn (string $slug) => $permissionsBySlug[$slug])
                ->values()
                ->all();

            $role->permissions()->sync($permissionIds);
        }

        Permission::query()
            ->whereNotIn('slug', $this->allSlugs()->all())
            ->each(function (Permission $permission) {
                $permission->roles()->detach();
                $permission->delete();
            });
    }

    /**
     * Permissions grouped by module for the role assignment UI.
     *
     * @return Collection<string, Collection<int, Permission>>
     */
    public function groupedForUi(): Collection
    {
        $permissions = Permission::query()
            ->orderBy('group')
            ->orderBy('name')
            ->get()
            ->keyBy('slug');

        return collect($this->modules())
            ->map(function (array $module, string $moduleKey) use ($permissions) {
                $modulePermissions = collect($module['actions'])
                    ->keys()
                    ->map(fn (string $action) => $permissions->get($this->slug($action, $moduleKey)))
                    ->filter();

                return [
                    'label' => $module['label'],
                    'permissions' => $modulePermissions,
                ];
            })
            ->filter(fn (array $module) => $module['permissions']->isNotEmpty());
    }
}
