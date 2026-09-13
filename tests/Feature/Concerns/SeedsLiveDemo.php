<?php

namespace Tests\Feature\Concerns;

use App\Models\Company;
use App\Models\Scopes\CompanyScope;
use App\Models\User;
use App\Support\CurrentCompany;
use Database\Seeders\DemoDataSeeder;
use Database\Seeders\PlanSeeder;
use Database\Seeders\RbacSeeder;

trait SeedsLiveDemo
{
    protected function seedLiveDemo(): Company
    {
        $this->seed(RbacSeeder::class);
        $this->seed(PlanSeeder::class);
        $this->seed(DemoDataSeeder::class);

        $company = Company::query()->where('slug', DemoDataSeeder::COMPANY_SLUG)->firstOrFail();
        app(CurrentCompany::class)->set($company);

        return $company;
    }

    protected function demoUser(string $key): User
    {
        return User::withoutGlobalScope(CompanyScope::class)
            ->where('email', DemoDataSeeder::USERS[$key]['email'])
            ->firstOrFail();
    }
}
