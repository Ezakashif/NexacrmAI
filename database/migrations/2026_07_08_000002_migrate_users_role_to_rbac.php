<?php

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    public function up(): void
    {
        (new RbacSeeder)->run();

        $roleMap = [
            'admin' => 'admin',
            'user' => 'sales',
            'sales' => 'sales',
            'manager' => 'manager',
        ];

        $rolesBySlug = Role::query()->pluck('id', 'slug');

        User::query()->each(function (User $user) use ($roleMap, $rolesBySlug) {
            $slug = $roleMap[$user->role] ?? 'sales';
            $roleId = $rolesBySlug[$slug] ?? null;

            if ($roleId) {
                $user->roles()->syncWithoutDetaching([$roleId]);
            }
        });
    }

    public function down(): void
    {
        //
    }
};
