<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Lead;
use App\Models\Scopes\CompanyScope;
use App\Models\User;
use App\Support\CurrentCompany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TenancyPhase1BFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_company_can_be_resolved(): void
    {
        $company = Company::default();

        $this->assertNotNull($company);
        $this->assertSame('default', $company->slug);
        $this->assertTrue($company->isActive());
    }

    public function test_current_company_is_a_singleton(): void
    {
        $first = app(CurrentCompany::class);
        $second = app(CurrentCompany::class);

        $this->assertSame($first, $second);
    }

    public function test_current_company_set_get_and_clear(): void
    {
        $company = Company::factory()->create();
        $current = app(CurrentCompany::class);

        $this->assertFalse($current->check());
        $this->assertNull($current->id());

        $current->set($company);

        $this->assertTrue($current->check());
        $this->assertSame($company->id, $current->id());
        $this->assertTrue($company->is($current->get()));
        $this->assertSame($company->id, $current->require());

        $current->clear();

        $this->assertFalse($current->check());
        $this->assertNull($current->id());
    }

    public function test_company_scope_is_noop_when_current_company_unset_outside_production(): void
    {
        config(['tenancy.fail_closed_without_context' => false]);

        $default = Company::default();
        $other = Company::factory()->create();

        DB::table('leads')->insert([
            [
                'name' => 'Default Lead',
                'email' => 'default-lead@example.com',
                'status' => 'new',
                'source' => 'website',
                'company_id' => $default->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Other Lead',
                'email' => 'other-lead@example.com',
                'status' => 'new',
                'source' => 'website',
                'company_id' => $other->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->assertSame(2, Lead::query()->count());
    }

    public function test_company_scope_fails_closed_when_configured(): void
    {
        config(['tenancy.fail_closed_without_context' => true]);

        $default = Company::default();

        DB::table('leads')->insert([
            [
                'name' => 'Hidden Lead',
                'email' => 'hidden-lead@example.com',
                'status' => 'new',
                'source' => 'website',
                'company_id' => $default->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->assertSame(0, Lead::query()->count());
        $this->assertSame(1, Lead::withoutCompanyScope()->count());
    }

    public function test_company_scope_filters_when_current_company_is_set(): void
    {
        $default = Company::default();
        $other = Company::factory()->create();

        DB::table('leads')->insert([
            [
                'name' => 'Default Lead',
                'email' => 'scoped-default@example.com',
                'status' => 'new',
                'source' => 'website',
                'company_id' => $default->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Other Lead',
                'email' => 'scoped-other@example.com',
                'status' => 'new',
                'source' => 'website',
                'company_id' => $other->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        app(CurrentCompany::class)->set($default);

        $this->assertSame(1, Lead::query()->count());
        $this->assertSame('Default Lead', Lead::query()->value('name'));
        $this->assertSame(2, Lead::query()->withoutCompanyScope()->count());
        $this->assertSame(1, Lead::query()->withoutGlobalScope(CompanyScope::class)->forCompany($other)->count());
    }

    public function test_creating_model_auto_sets_company_id_from_current_company(): void
    {
        $company = Company::factory()->create();
        app(CurrentCompany::class)->set($company);

        $user = User::factory()->create([
            'email' => 'tenant-user@example.com',
            // Allow BelongsToCompany to fill from CurrentCompany.
            'company_id' => null,
        ]);

        $this->assertSame($company->id, $user->company_id);
        $this->assertTrue($user->company->is($company));
    }

    public function test_users_can_still_be_created_without_company_for_super_admin_prep(): void
    {
        $userId = DB::table('users')->insertGetId([
            'name' => 'Platform Admin',
            'email' => 'platform-admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'status' => 'active',
            'company_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertNull(DB::table('users')->where('id', $userId)->value('company_id'));
    }
}
