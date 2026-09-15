<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed platform defaults only.
     *
     * Does not create login accounts or demo CRM records. The first Super Admin
     * is created only when SETUP_SUPERADMIN_EMAIL and SETUP_SUPERADMIN_PASSWORD
     * are set (see SuperAdminSeeder), or afterwards via:
     *
     *   php artisan nexacrm:create-super-admin
     *
     * Optional fictional demo tenant:
     *
     *   DEMO_SEED=true php artisan db:seed
     *   php artisan db:seed --class=DemoDataSeeder
     */
    public function run(): void
    {
        config(['tenancy.fail_closed_without_context' => false]);

        $this->command?->info('Seeding NexaCRM platform defaults (permissions, email templates, plans).');

        $this->call([
            RbacSeeder::class,
            EmailTemplateSeeder::class,
            PlanSeeder::class,
            SuperAdminSeeder::class,
        ]);

        if (config('setup.demo_seed')) {
            $this->command?->warn('DEMO_SEED is enabled; seeding the optional fictional demo tenant.');
            $this->call(DemoDataSeeder::class);
        }
    }
}
