<?php

use App\Services\PermissionRegistry;
use App\Services\RbacRoleSynchronizer;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistry::class)->sync();
        app(RbacRoleSynchronizer::class)->syncDefaultRoles();
    }

    public function down(): void
    {
        //
    }
};
