<?php

/**
 * CI/local helper: prove normal db:seed did not create the demo tenant,
 * and that the Super Admin created during the install path exists.
 *
 * Usage (after migrate + seed + nexacrm:create-super-admin):
 *   php scripts/ci-assert-normal-seed.php
 */

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Company;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Scopes\CompanyScope;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Support\Facades\DB;

$demoCompanies = Company::query()->where('slug', DemoDataSeeder::COMPANY_SLUG)->count();
if ($demoCompanies !== 0) {
    fwrite(STDERR, "Normal seed created the optional demo tenant.\n");
    exit(1);
}

$superAdmins = User::withoutGlobalScope(CompanyScope::class)
    ->where('is_super_admin', true)
    ->count();
if ($superAdmins < 1) {
    fwrite(STDERR, "Expected a Super Admin after the install path.\n");
    exit(1);
}

$leads = Lead::withoutGlobalScope(CompanyScope::class)->count();
$customers = Customer::withoutGlobalScope(CompanyScope::class)->count();
if ($leads !== 0 || $customers !== 0) {
    fwrite(STDERR, "Normal seed inserted CRM sample records (leads={$leads}, customers={$customers}).\n");
    exit(1);
}

$plans = DB::table('plans')->count();
if ($plans < 1) {
    fwrite(STDERR, "Normal seed did not create plans.\n");
    exit(1);
}

fwrite(STDOUT, "Normal seed OK: Super Admin present, demo tenant absent, no sample CRM records.\n");
exit(0);
