<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class PermissionRegistrar
{
    public function registerGates(): void
    {
        try {
            $permissions = Permission::query()->pluck('slug');
        } catch (\Throwable) {
            return;
        }

        foreach ($permissions as $slug) {
            Gate::define($slug, fn (User $user) => $user->hasPermission($slug));
        }

        Gate::define('access-reports', fn (User $user) => $user->canAccessReports());
        Gate::define('access-search', fn (User $user) => $user->hasAnyPermission([
            'view.leads',
            'view.customers',
            'view.tasks',
            'view.users',
        ]));
        Gate::define('access-activity-logs', fn (User $user) => $user->hasAnyPermission([
            'view.activity_logs',
            'view_own.activity_logs',
        ]));
        Gate::define('access-company-settings', fn (User $user) => $user->isAdmin()
            || $user->hasPermission('update.company_settings'));
    }

    public function refreshGates(): void
    {
        $this->registerGates();
    }
}
