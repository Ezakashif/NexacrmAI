<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Scopes\CompanyScope;
use App\Models\User;
use App\Support\CurrentCompany;
use Database\Seeders\DemoDataSeeder;
use Database\Seeders\PlanSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoDataEnvironmentPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
        $this->seed(PlanSeeder::class);
        $this->seed(DemoDataSeeder::class);
    }

    public function test_demo_admin_can_open_core_crm_pages(): void
    {
        $admin = User::withoutGlobalScope(CompanyScope::class)
            ->where('email', DemoDataSeeder::USERS['admin']['email'])
            ->firstOrFail();

        $company = Company::query()->where('slug', DemoDataSeeder::COMPANY_SLUG)->firstOrFail();
        app(CurrentCompany::class)->set($company);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('BrightPath Consulting', false);

        $this->actingAs($admin)->get(route('leads.index'))->assertOk()->assertSee('crm-kanban', false);
        $this->actingAs($admin)->get(route('customers.index'))->assertOk()->assertSee('ClearView Systems', false);
        $this->actingAs($admin)->get(route('tasks.index'))->assertOk()->assertSee('crm-kanban', false);
        $this->actingAs($admin)->get(route('reports.index'))->assertOk();
    }

    public function test_demo_sales_rep_does_not_receive_admin_role(): void
    {
        $sales = User::withoutGlobalScope(CompanyScope::class)
            ->where('email', DemoDataSeeder::USERS['sales']['email'])
            ->firstOrFail();

        $this->assertTrue($sales->hasRole('sales'));
        $this->assertFalse($sales->hasRole('admin'));
        $this->assertFalse($sales->canViewAllLeads());
    }
}
