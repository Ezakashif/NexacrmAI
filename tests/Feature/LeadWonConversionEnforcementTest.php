<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadWonConversionEnforcementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    public function test_lead_update_cannot_set_won_without_conversion(): void
    {
        $user = User::factory()->admin()->create();
        $lead = Lead::factory()->create([
            'company_id' => $user->company_id,
            'created_by' => $user->id,
            'assigned_to' => $user->id,
            'status' => 'qualified',
        ]);

        $this->actingAs($user)
            ->put(route('leads.update', $lead), [
                'name' => $lead->name,
                'status' => 'won',
            ])
            ->assertSessionHasErrors('status');

        $this->assertSame('qualified', $lead->fresh()->status);
        $this->assertDatabaseMissing('customers', [
            'source_lead_id' => $lead->id,
        ]);
    }

    public function test_kanban_cannot_move_lead_to_won(): void
    {
        $user = User::factory()->admin()->create();
        $lead = Lead::factory()->create([
            'company_id' => $user->company_id,
            'created_by' => $user->id,
            'assigned_to' => $user->id,
            'status' => 'new',
        ]);

        $this->actingAs($user)
            ->postJson(route('leads.board.update'), [
                'lead_id' => $lead->id,
                'status' => 'won',
                'sort_order' => 1,
            ])
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);

        $this->assertSame('new', $lead->fresh()->status);
    }

    public function test_convert_to_customer_marks_lead_won_and_creates_customer(): void
    {
        $user = User::factory()->admin()->create();
        $lead = Lead::factory()->create([
            'company_id' => $user->company_id,
            'created_by' => $user->id,
            'assigned_to' => $user->id,
            'status' => 'proposal_sent',
            'name' => 'Convert Me',
            'email' => 'convert@example.com',
        ]);

        $this->actingAs($user)
            ->post(route('leads.convert', $lead))
            ->assertRedirect();

        $lead->refresh();
        $this->assertSame('won', $lead->status);

        $customer = Customer::query()->where('source_lead_id', $lead->id)->first();
        $this->assertNotNull($customer);
        $this->assertSame('Convert Me', $customer->name);
    }

    public function test_orphan_won_lead_can_still_be_converted(): void
    {
        $user = User::factory()->admin()->create();
        $lead = Lead::factory()->create([
            'company_id' => $user->company_id,
            'created_by' => $user->id,
            'assigned_to' => $user->id,
            'status' => 'won',
            'name' => 'Orphan Won',
        ]);

        $this->actingAs($user)
            ->post(route('leads.convert', $lead))
            ->assertRedirect();

        $this->assertDatabaseHas('customers', [
            'source_lead_id' => $lead->id,
            'name' => 'Orphan Won',
        ]);
    }
}
