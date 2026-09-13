<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UiPhase2CSuperAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    public function test_super_admin_shell_includes_shared_ui_assets(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->get(route('superadmin.dashboard'))
            ->assertOk()
            ->assertSee('css/sa-app.css', false)
            ->assertSee('js/sa-ui.js', false)
            ->assertSee('sa-toast-stack', false)
            ->assertSee('sa-confirm-backdrop', false)
            ->assertSee('sa-flash-data', false)
            ->assertSee('class="sa-app"', false);
    }

    public function test_companies_index_uses_confirm_attrs_and_empty_state(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->get(route('superadmin.companies.index', ['search' => 'zzzz-no-match-company']))
            ->assertOk()
            ->assertSee('No companies found')
            ->assertSee('Clear filters')
            ->assertSee('sa-empty', false);

        $this->actingAs($superAdmin)
            ->get(route('superadmin.companies.index'))
            ->assertOk()
            ->assertSee('data-sa-confirm', false)
            ->assertSee('sa-toolbar', false)
            ->assertSee('sa-filter-bar', false)
            ->assertSee('Apply filters', false)
            ->assertSee('New company', false)
            ->assertSee('Import', false)
            ->assertSee('Export CSV', false)
            ->assertSee('Export PDF', false)
            ->assertDontSee("onclick=\"return confirm(", false);
    }

    public function test_company_create_and_settings_use_form_sections(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->get(route('superadmin.companies.create'))
            ->assertOk()
            ->assertSee('sa-form-section', false)
            ->assertSee('sa-required', false)
            ->assertSee('Company details');

        $this->actingAs($superAdmin)
            ->get(route('superadmin.settings.edit'))
            ->assertOk()
            ->assertSee('sa-form-section', false)
            ->assertSee('Platform identity')
            ->assertSee('Access controls');
    }

    public function test_search_and_super_admins_pages_render_polished_shell(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->get(route('superadmin.search.index'))
            ->assertOk()
            ->assertSee('Search the platform')
            ->assertSee('sa-empty', false);

        $this->actingAs($superAdmin)
            ->get(route('superadmin.super-admins.index'))
            ->assertOk()
            ->assertSee('Create Super Admin')
            ->assertSee('css/sa-app.css', false);
    }
}
