<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UiPhase2CRolesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    public function test_roles_index_uses_shared_header_and_empty_filter_state(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('roles.index', ['search' => 'zzzz-no-match']))
            ->assertOk()
            ->assertSee('crm-page-header', false)
            ->assertSee('No roles match your filters')
            ->assertSee('Clear filters');
    }

    public function test_roles_create_and_edit_use_shared_patterns(): void
    {
        $admin = User::factory()->admin()->create();
        $role = Role::query()
            ->where('company_id', $admin->company_id)
            ->where('is_system', false)
            ->first()
            ?? Role::factory()->create([
                'company_id' => $admin->company_id,
                'name' => 'Support Agent',
                'slug' => 'support_agent',
                'is_system' => false,
            ]);

        $this->actingAs($admin)
            ->get(route('roles.create'))
            ->assertOk()
            ->assertSee('crm-form-section', false)
            ->assertSee('Create role')
            ->assertSee('permission-checklist', false);

        $this->actingAs($admin)
            ->get(route('roles.edit', $role))
            ->assertOk()
            ->assertSee('Edit role')
            ->assertSee('Permissions')
            ->assertSee('permission-checklist', false);
    }
}
