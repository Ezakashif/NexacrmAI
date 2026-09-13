<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    public function test_sales_user_creating_lead_without_assign_permission_auto_assigns_to_self(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->assertFalse($user->canAssignLeads());

        $response = $this->actingAs($user)->post(route('leads.store'), [
            'name' => 'Auto Assigned Lead',
            'status' => 'new',
            'assigned_to' => $other->id,
        ]);

        $lead = Lead::query()->where('name', 'Auto Assigned Lead')->firstOrFail();

        $response->assertRedirect(route('leads.show', $lead));
        $this->assertSame($user->id, $lead->assigned_to);
    }

    public function test_admin_can_assign_lead_to_another_user(): void
    {
        $admin = User::factory()->admin()->create();
        $sales = User::factory()->create();

        $response = $this->actingAs($admin)->post(route('leads.store'), [
            'name' => 'Admin Assigned Lead',
            'status' => 'new',
            'assigned_to' => $sales->id,
        ]);

        $lead = Lead::query()->where('name', 'Admin Assigned Lead')->firstOrFail();

        $response->assertRedirect(route('leads.show', $lead));
        $this->assertSame($sales->id, $lead->assigned_to);
    }

    public function test_sales_user_only_sees_assigned_leads_on_index(): void
    {
        $viewer = User::factory()->create();
        $other = User::factory()->create();

        Lead::factory()->assignedTo($viewer)->create([
            'created_by' => $viewer->id,
            'name' => 'Mine',
        ]);

        Lead::factory()->assignedTo($other)->create([
            'created_by' => $other->id,
            'name' => 'Theirs',
        ]);

        $response = $this->actingAs($viewer)->get(route('leads.index'));

        $response->assertOk();
        $response->assertSee('Mine');
        $response->assertDontSee('Theirs');
    }

    public function test_sales_user_cannot_view_unassigned_lead(): void
    {
        $user = User::factory()->create();
        $lead = Lead::factory()->create([
            'created_by' => $user->id,
            'assigned_to' => null,
            'name' => 'Unassigned',
        ]);

        $this->actingAs($user)
            ->get(route('leads.show', $lead))
            ->assertForbidden();
    }

    public function test_admin_can_view_all_leads(): void
    {
        $admin = User::factory()->admin()->create();
        $sales = User::factory()->create();

        Lead::factory()->assignedTo($sales)->create([
            'created_by' => $sales->id,
            'name' => 'Sales Lead',
        ]);

        Lead::factory()->create([
            'created_by' => $admin->id,
            'assigned_to' => null,
            'name' => 'Open Lead',
        ]);

        $response = $this->actingAs($admin)->get(route('leads.index'));

        $response->assertOk();
        $response->assertSee('Sales Lead');
        $response->assertSee('Open Lead');
    }

    public function test_sales_user_cannot_reassign_lead_without_assign_permission(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $lead = Lead::factory()->assignedTo($user)->create([
            'created_by' => $user->id,
            'name' => 'Owned Lead',
            'status' => 'new',
        ]);

        $this->actingAs($user)->put(route('leads.update', $lead), [
            'name' => 'Owned Lead',
            'status' => 'contacted',
            'assigned_to' => $other->id,
        ])->assertRedirect(route('leads.show', $lead));

        $this->assertSame($user->id, $lead->fresh()->assigned_to);
        $this->assertSame('contacted', $lead->fresh()->status);
    }
}
