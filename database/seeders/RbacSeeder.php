<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Services\PermissionRegistry;
use App\Services\RbacRoleSynchronizer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class RbacSeeder extends Seeder
{
    public const ROLES = [
        'admin' => [
            'name' => 'Administrator',
            'description' => 'Full system access.',
            'is_system' => true,
        ],
        'sales' => [
            'name' => 'Sales Representative',
            'description' => 'Standard CRM access for sales staff.',
            'is_system' => false,
        ],
    ];

    public const ROLE_PERMISSIONS = [
        'admin' => '*',
        'sales' => [
            'view.customers', 'create.customers', 'import.customers', 'update.customers', 'delete.customers',
            'view.leads', 'create.leads', 'import.leads', 'update.leads', 'delete.leads', 'convert.leads', 'log.leads',
            'view.tasks', 'change_status.tasks', 'update.tasks', 'delete.tasks',
            'view_own.activity_logs',
            'view.notifications',
            'view.reports', 'export.reports',
            'view.channels',
            'view.inbox', 'reply.inbox',
        ],
    ];

    public function run(): void
    {
        app(PermissionRegistry::class)->sync();

        $synchronizer = app(RbacRoleSynchronizer::class);

        // Historical migrations invoke this seeder before the companies table exists.
        if (! Schema::hasTable('companies') || ! Schema::hasColumn('roles', 'company_id')) {
            $synchronizer->syncDefaultRoles();

            return;
        }

        $company = Company::query()->firstOrCreate(
            ['slug' => Company::DEFAULT_SLUG],
            [
                'name' => 'Default Company',
                'status' => Company::STATUS_ACTIVE,
            ],
        );

        $synchronizer->syncDefaultRolesForCompany($company);
    }
}
