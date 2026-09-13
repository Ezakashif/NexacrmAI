<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UiPhase2CUsersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    public function test_users_index_uses_shared_header_and_empty_state(): void
    {
        $admin = User::factory()->admin()->create();

        // Soft-filter so only the authenticated admin remains visible when searching for a nonsense term.
        $this->actingAs($admin)
            ->get(route('users.index', ['search' => 'zzzz-no-match']))
            ->assertOk()
            ->assertSee('crm-page-header', false)
            ->assertSee('No users match your filters')
            ->assertSee('Clear filters');
    }

    public function test_users_create_edit_show_use_shared_patterns(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create([
            'name' => 'Teammate One',
            'company_id' => $admin->company_id,
        ]);

        $this->actingAs($admin)
            ->get(route('users.create'))
            ->assertOk()
            ->assertSee('crm-form-section', false)
            ->assertSee('Create user')
            ->assertSee('crm-required', false)
            ->assertSee('data-image-crop-upload', false)
            ->assertSee('Drop a photo here');

        $this->actingAs($admin)
            ->get(route('users.edit', $user))
            ->assertOk()
            ->assertSee('Edit user')
            ->assertSee('Teammate One')
            ->assertSee('data-image-crop-upload', false)
            ->assertSee('Adjust photo in frame');

        $this->actingAs($admin)
            ->get(route('users.show', $user))
            ->assertOk()
            ->assertSee('Profile details')
            ->assertSee('Delete user')
            ->assertSee('data-crm-confirm', false);
    }
}
