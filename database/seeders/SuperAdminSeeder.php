<?php

namespace Database\Seeders;

use App\Services\Setup\SuperAdminBootstrap;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Creates the first Super Admin only when SETUP_SUPERADMIN_EMAIL and
 * SETUP_SUPERADMIN_PASSWORD are both set. Never invents a default password.
 */
class SuperAdminSeeder extends Seeder
{
    public function run(SuperAdminBootstrap $bootstrap): void
    {
        if ($bootstrap->hasExistingSuperAdmin()) {
            $this->command?->info('Super Admin already exists; skipping first-run creation.');

            return;
        }

        if ($bootstrap->hasPartialConfiguredCredentials()) {
            throw new RuntimeException(
                'Both SETUP_SUPERADMIN_EMAIL and SETUP_SUPERADMIN_PASSWORD must be set to create a Super Admin during seed. Leave both empty and run php artisan nexacrm:create-super-admin instead.'
            );
        }

        if (! $bootstrap->hasConfiguredCredentials()) {
            $this->command?->warn(
                'No Super Admin created. After seeding, run: php artisan nexacrm:create-super-admin'
            );

            return;
        }

        $user = $bootstrap->create(
            $bootstrap->configuredName(),
            (string) $bootstrap->configuredEmail(),
            (string) $bootstrap->configuredPassword(),
            'seeder',
        );

        $this->command?->info('Super Admin created from SETUP_SUPERADMIN_* environment variables: '.$user->email);
        $this->command?->comment('Remove SETUP_SUPERADMIN_PASSWORD from the environment after first-run.');
    }
}
