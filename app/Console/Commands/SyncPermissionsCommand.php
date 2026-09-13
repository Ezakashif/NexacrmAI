<?php

namespace App\Console\Commands;

use App\Services\PermissionRegistrar;
use App\Services\PermissionRegistry;
use App\Services\RbacRoleSynchronizer;
use Illuminate\Console\Command;

class SyncPermissionsCommand extends Command
{
    protected $signature = 'permissions:sync';

    protected $description = 'Sync permissions and default roles from the RBAC registry';

    public function handle(
        PermissionRegistry $registry,
        PermissionRegistrar $registrar,
        RbacRoleSynchronizer $roleSynchronizer,
    ): int {
        $registry->sync();
        $roleSynchronizer->syncDefaultRoles();
        $registrar->refreshGates();

        $count = $registry->allSlugs()->count();
        $companies = \App\Models\Company::query()->count();
        $this->info("Synced {$count} permissions from registry.");
        $this->info("Default roles updated for {$companies} company(ies). Only Administrator remains a system role.");

        return self::SUCCESS;
    }
}

