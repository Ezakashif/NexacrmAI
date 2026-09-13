<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Support\CurrentCompany;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TenancyPhase1CMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    public function test_authenticated_request_sets_current_company(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk();

        // Middleware clears context in terminate(); assert via a route callback instead.
        $observed = null;

        \Illuminate\Support\Facades\Route::get('/__test/current-company', function (CurrentCompany $current) use (&$observed) {
            $observed = $current->id();

            return response()->noContent();
        })->middleware(['web', 'auth', 'active', 'company']);

        $this->actingAs($user)->get('/__test/current-company')->assertNoContent();

        $this->assertSame($user->company_id, $observed);
    }

    public function test_user_without_company_is_logged_out_from_crm(): void
    {
        $user = User::factory()->create();

        DB::table('users')->where('id', $user->id)->update(['company_id' => null]);
        $user->refresh();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_user_from_suspended_company_is_logged_out(): void
    {
        $company = Company::factory()->suspended()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_tenant_user_still_reaches_dashboard(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_company_scope_applies_during_authenticated_request(): void
    {
        $default = Company::default();
        $other = Company::factory()->create();
        $user = User::factory()->admin()->create(['company_id' => $default->id]);

        DB::table('leads')->insert([
            [
                'name' => 'Mine',
                'email' => 'mine-tenant@example.com',
                'status' => 'new',
                'source' => 'website',
                'company_id' => $default->id,
                'created_by' => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Other Co',
                'email' => 'other-tenant@example.com',
                'status' => 'new',
                'source' => 'website',
                'company_id' => $other->id,
                'created_by' => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $visibleNames = null;

        \Illuminate\Support\Facades\Route::get('/__test/visible-leads', function () use (&$visibleNames) {
            $visibleNames = \App\Models\Lead::query()->orderBy('name')->pluck('name')->all();

            return response()->noContent();
        })->middleware(['web', 'auth', 'active', 'company']);

        $this->actingAs($user)->get('/__test/visible-leads')->assertNoContent();

        $this->assertSame(['Mine'], $visibleNames);
    }
}
