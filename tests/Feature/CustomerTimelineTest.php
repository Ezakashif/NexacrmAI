<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\Task;
use App\Models\User;
use App\Services\CustomerTimelineService;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerTimelineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    public function test_conversion_links_source_lead_and_shows_timeline_events(): void
    {
        $user = User::factory()->create();
        $lead = Lead::factory()->assignedTo($user)->create([
            'created_by' => $user->id,
            'name' => 'Timeline Lead',
            'email' => 'timeline-lead@example.com',
            'status' => 'qualified',
        ]);

        LeadActivity::log($lead, 'call', 'Discovery call', now()->subDays(2), now()->addDay());
        LeadActivity::log($lead, 'meeting', 'Demo meeting', now()->subDay());
        LeadActivity::log($lead, 'note', 'Interested in annual plan', now()->subHours(5));

        Task::factory()->assignedTo($user)->create([
            'created_by' => $user->id,
            'lead_id' => $lead->id,
            'title' => 'Send proposal',
            'status' => 'completed',
            'completed_at' => now()->subHours(2),
        ]);

        $this->actingAs($user)
            ->post(route('leads.convert', $lead))
            ->assertRedirect();

        $customer = Customer::query()->where('email', 'timeline-lead@example.com')->first();
        $this->assertNotNull($customer);
        $this->assertSame($lead->id, $customer->source_lead_id);
        $this->assertDatabaseHas('tasks', [
            'lead_id' => $lead->id,
            'customer_id' => $customer->id,
            'title' => 'Send proposal',
        ]);

        $response = $this->actingAs($user)->get(route('customers.show', $customer));

        $response->assertOk()
            ->assertSee('Customer timeline')
            ->assertSee('Customer Created')
            ->assertSee('Lead Converted')
            ->assertSee('Lead Assigned')
            ->assertSee('Call Logged')
            ->assertSee('Meeting Scheduled')
            ->assertSee('Follow-up')
            ->assertSee('Notes')
            ->assertSee('Task Created')
            ->assertSee('Task Completed')
            ->assertSee('Discovery call')
            ->assertSee('Send proposal');
    }

    public function test_sales_user_does_not_see_other_reps_lead_history_on_customer(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();

        $lead = Lead::factory()->assignedTo($owner)->create([
            'created_by' => $owner->id,
            'name' => 'Private Lead',
            'email' => 'private-lead@example.com',
            'status' => 'won',
        ]);

        LeadActivity::log($lead, 'note', 'Secret note', now()->subDay(), null, $owner->id);

        $customer = Customer::factory()->create([
            'created_by' => $owner->id,
            'source_lead_id' => $lead->id,
            'name' => 'Shared Customer',
            'email' => 'private-lead@example.com',
        ]);

        $timeline = app(CustomerTimelineService::class)->forCustomer($customer, $viewer);

        $this->assertTrue($timeline->contains(fn ($event) => $event->type === 'customer_created'));
        $this->assertFalse($timeline->contains(fn ($event) => $event->type === 'notes' && $event->summary === 'Secret note'));
        $this->assertFalse($timeline->contains(fn ($event) => $event->type === 'lead_converted'));
    }

    public function test_backfill_links_won_lead_by_email(): void
    {
        $admin = User::factory()->admin()->create();

        $lead = Lead::factory()->assignedTo($admin)->create([
            'created_by' => $admin->id,
            'email' => 'backfill@example.com',
            'status' => 'won',
            'name' => 'Backfill Lead',
        ]);

        $customer = Customer::factory()->create([
            'created_by' => $admin->id,
            'email' => 'backfill@example.com',
            'source_lead_id' => null,
            'name' => 'Backfill Customer',
        ]);

        $migration = require database_path('migrations/2026_07_10_180000_add_source_lead_id_to_customers_table.php');
        $method = new \ReflectionMethod($migration, 'backfillSourceLeads');
        $method->setAccessible(true);
        $method->invoke($migration);

        $this->assertSame($lead->id, $customer->fresh()->source_lead_id);
    }
}
