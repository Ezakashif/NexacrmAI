<?php

use App\Services\PermissionRegistry;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistry::class)->migrateLegacyRolePermissions();
    }

    public function down(): void
    {
        //
    }
};
