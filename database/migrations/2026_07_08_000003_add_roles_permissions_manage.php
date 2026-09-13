<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            ['name' => 'Manage Roles', 'slug' => 'roles.manage', 'group' => 'admin'],
            ['name' => 'Manage Permissions', 'slug' => 'permissions.manage', 'group' => 'admin'],
        ];

        foreach ($permissions as $attributes) {
            Permission::query()->updateOrCreate(
                ['slug' => $attributes['slug']],
                $attributes,
            );
        }

        $adminRole = Role::query()->where('slug', 'admin')->first();

        if ($adminRole) {
            $adminRole->permissions()->syncWithoutDetaching(
                Permission::query()->pluck('id')->all()
            );
        }
    }

    public function down(): void
    {
        $slugs = ['roles.manage', 'permissions.manage'];

        Permission::query()->whereIn('slug', $slugs)->each(function (Permission $permission) {
            $permission->roles()->detach();
            $permission->delete();
        });
    }
};
