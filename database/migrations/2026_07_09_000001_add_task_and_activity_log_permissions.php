<?php

use App\Models\Permission;
use App\Models\Role;
use App\Services\PermissionRegistry;
use Database\Seeders\RbacSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistry::class)->sync();

        $permissionsBySlug = Permission::query()->pluck('id', 'slug');

        foreach (RbacSeeder::ROLE_PERMISSIONS as $slug => $permissionSlugs) {
            $role = Role::query()->where('slug', $slug)->first();

            if (! $role) {
                continue;
            }

            if ($permissionSlugs === '*') {
                $role->permissions()->sync($permissionsBySlug->values()->all());

                continue;
            }

            $permissionIds = collect($permissionSlugs)
                ->filter(fn (string $permissionSlug) => $permissionsBySlug->has($permissionSlug))
                ->map(fn (string $permissionSlug) => $permissionsBySlug[$permissionSlug])
                ->all();

            $role->permissions()->syncWithoutDetaching($permissionIds);
        }
    }

    public function down(): void
    {
        //
    }
};
