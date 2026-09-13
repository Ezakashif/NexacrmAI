<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UiPhase2CImportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    public function test_import_form_uses_shared_header_and_form_sections(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('imports.create', 'leads'))
            ->assertOk()
            ->assertSee('crm-page-header', false)
            ->assertSee('crm-form-section', false)
            ->assertSee('crm-required', false)
            ->assertSee('Import Leads')
            ->assertSee('CSV upload')
            ->assertSee('Sample CSV')
            ->assertSee('Download sample CSV');
    }

    public function test_customer_and_user_import_forms_render(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('imports.create', 'customers'))
            ->assertOk()
            ->assertSee('Import Customers')
            ->assertSee('crm-page-header', false);

        $this->actingAs($admin)
            ->get(route('imports.create', 'users'))
            ->assertOk()
            ->assertSee('Import Users')
            ->assertSee('roles');
    }
}
