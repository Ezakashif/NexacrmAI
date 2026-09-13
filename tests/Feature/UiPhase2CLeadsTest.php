<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UiPhase2CLeadsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    public function test_leads_index_uses_shared_page_header_and_empty_state(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get(route('leads.index'));

        $response->assertOk();
        $response->assertSee('crm-page-header', false);
        $response->assertSee('Track and move deals across your pipeline.');
        $response->assertSee('No leads yet');
        $response->assertSee('Add lead');
        $response->assertSee('crm-kanban', false);
        $response->assertSee('crm-kanban-stages', false);
        $response->assertDontSee('alert-success', false);
    }

    public function test_leads_board_shows_mobile_status_control_for_cards(): void
    {
        $user = User::factory()->admin()->create();
        Lead::factory()->create([
            'company_id' => $user->company_id,
            'assigned_to' => $user->id,
            'created_by' => $user->id,
            'status' => 'new',
            'name' => 'Mobile Board Lead',
        ]);

        $this->actingAs($user)
            ->get(route('leads.index'))
            ->assertOk()
            ->assertSee('crm-kanban-status', false)
            ->assertSee('Mobile Board Lead');
    }

    public function test_leads_create_and_show_use_form_sections_and_confirm(): void
    {
        $user = User::factory()->admin()->create();
        $lead = Lead::factory()->create([
            'created_by' => $user->id,
            'assigned_to' => $user->id,
            'name' => 'Pipeline Prospect',
            'status' => 'qualified',
        ]);

        $this->actingAs($user)
            ->get(route('leads.create'))
            ->assertOk()
            ->assertSee('crm-form-section', false)
            ->assertSee('Create lead')
            ->assertSee('crm-required', false);

        $this->actingAs($user)
            ->get(route('leads.show', $lead))
            ->assertOk()
            ->assertSee('Pipeline Prospect')
            ->assertSee('data-crm-confirm', false)
            ->assertSee('Activity timeline')
            ->assertSee('Convert to customer');
    }
}
