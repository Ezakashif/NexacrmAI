<?php

use App\Models\Permission;
use App\Models\Role;
use App\Services\PermissionRegistry;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistry::class)->sync();

        $reportPermissionIds = Permission::query()
            ->whereIn('slug', ['view.reports', 'export.reports'])
            ->pluck('id')
            ->all();

        if ($reportPermissionIds === []) {
            return;
        }

        $crmPermissionIds = Permission::query()
            ->whereIn('slug', ['view.leads', 'view.tasks', 'view.customers'])
            ->pluck('id');

        if ($crmPermissionIds->isEmpty()) {
            return;
        }

        Role::query()
            ->whereHas('permissions', function ($query) use ($crmPermissionIds) {
                $query->whereIn('permissions.id', $crmPermissionIds);
            })
            ->each(function (Role $role) use ($reportPermissionIds) {
                $role->permissions()->syncWithoutDetaching($reportPermissionIds);
            });
    }

    public function down(): void
    {
        //
    }
};
