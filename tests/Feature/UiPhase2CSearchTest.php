<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UiPhase2CSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    public function test_search_index_uses_shared_header_and_empty_prompt(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user)
            ->get(route('search.index'))
            ->assertOk()
            ->assertSee('crm-page-header', false)
            ->assertSee('Search the CRM')
            ->assertSee('crm-empty', false)
            ->assertSee('crm-filter-card', false);
    }

    public function test_search_no_matches_uses_empty_state(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user)
            ->get(route('search.index', ['q' => 'zzzz-no-match-term']))
            ->assertOk()
            ->assertSee('No matches found')
            ->assertSee('Clear search');
    }

    public function test_search_results_keep_category_sections(): void
    {
        $admin = User::factory()->admin()->create();

        Lead::factory()->assignedTo($admin)->create([
            'created_by' => $admin->id,
            'name' => 'Searchable Lead Alpha',
            'company' => 'Searchable Co',
        ]);

        $this->actingAs($admin)
            ->get(route('search.index', ['q' => 'Searchable']))
            ->assertOk()
            ->assertSee('Searchable Lead Alpha')
            ->assertSee('Leads')
            ->assertSee('crm-page-header', false);
    }
}
