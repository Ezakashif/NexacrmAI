<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_lead_show_page(): void
    {
        $user = User::factory()->create();
        $lead = Lead::factory()->assignedTo($user)->create(['name' => 'John Smith']);

        $this->actingAs($user)
            ->get(route('leads.show', $lead))
            ->assertOk()
            ->assertSee('John Smith')
            ->assertSee('Log activity');
    }

    public function test_user_can_log_activity_and_update_follow_up_date(): void
    {
        $user = User::factory()->create();
        $lead = Lead::factory()->assignedTo($user)->create(['status' => 'new', 'follow_up_date' => null]);

        $this->actingAs($user)
            ->post(route('leads.activities.store', $lead), [
                'type' => 'call',
                'summary' => 'Discussed pricing and timeline.',
                'occurred_at' => now()->format('Y-m-d\TH:i'),
                'next_follow_up_date' => now()->addDays(3)->format('Y-m-d'),
            ])
            ->assertRedirect(route('leads.show', $lead));

        $this->assertDatabaseHas('lead_activities', [
            'lead_id' => $lead->id,
            'user_id' => $user->id,
            'type' => 'call',
            'summary' => 'Discussed pricing and timeline.',
        ]);

        $lead->refresh();
        $this->assertSame('contacted', $lead->status);
        $this->assertSame(now()->addDays(3)->format('Y-m-d'), $lead->follow_up_date->format('Y-m-d'));
    }

    public function test_kanban_status_change_logs_activity(): void
    {
        $user = User::factory()->create();
        $lead = Lead::factory()->assignedTo($user)->create(['status' => 'new']);

        $this->actingAs($user)
            ->postJson(route('leads.board.update'), [
                'lead_id' => $lead->id,
                'status' => 'qualified',
                'sort_order' => 1,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('lead_activities', [
            'lead_id' => $lead->id,
            'type' => 'status_change',
        ]);

        $activity = LeadActivity::where('lead_id', $lead->id)->first();
        $this->assertStringContainsString('New', $activity->summary);
        $this->assertStringContainsString('Qualified', $activity->summary);
    }
}
