<?php

namespace Tests\Unit\Services;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\Task;
use App\Models\User;
use App\Services\CustomerTimelineService;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerTimelineServiceTest extends TestCase
{
    use RefreshDatabase;

    private CustomerTimelineService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
        $this->service = app(CustomerTimelineService::class);
    }

    public function test_for_customer_assembles_conversion_lead_and_task_events(): void
    {
        $user = User::factory()->create();
        $lead = Lead::factory()->assignedTo($user)->create([
            'created_by' => $user->id,
            'name' => 'Timeline Lead',
            'email' => 'timeline-lead@example.com',
            'status' => 'qualified',
        ]);

        LeadActivity::log($lead, 'call', 'Discovery call', now()->subDays(2), now()->addDay(), $user->id);
        LeadActivity::log($lead, 'note', 'Interested in annual plan', now()->subHours(5), null, $user->id);

        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'lead.assigned',
            'subject_type' => Lead::class,
            'subject_id' => $lead->id,
            'properties' => ['to' => $user->name],
            'ip_address' => '127.0.0.1',
        ]);

        $customer = Customer::factory()->create([
            'created_by' => $user->id,
            'source_lead_id' => $lead->id,
            'name' => 'Timeline Lead',
            'email' => 'timeline-lead@example.com',
        ]);

        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'lead.converted',
            'subject_type' => Lead::class,
            'subject_id' => $lead->id,
            'properties' => ['customer_id' => $customer->id],
            'ip_address' => '127.0.0.1',
        ]);

        Task::factory()->assignedTo($user)->create([
            'created_by' => $user->id,
            'customer_id' => $customer->id,
            'lead_id' => $lead->id,
            'title' => 'Send proposal',
            'status' => 'completed',
            'completed_at' => now()->subHours(2),
        ]);

        $events = $this->service->forCustomer($customer, $user);
        $types = $events->pluck('type')->all();

        $this->assertContains('customer_created', $types);
        $this->assertContains('lead_converted', $types);
        $this->assertContains('lead_assigned', $types);
        $this->assertContains('call_logged', $types);
        $this->assertContains('notes', $types);
        $this->assertContains('follow_up', $types);
        $this->assertContains('task_created', $types);
        $this->assertContains('task_completed', $types);
        $this->assertSame(
            $events->first()->occurredAt->getTimestamp(),
            $events->sortByDesc(fn ($event) => $event->occurredAt->getTimestamp())->first()->occurredAt->getTimestamp(),
        );
    }

    public function test_sales_user_does_not_see_other_reps_lead_history(): void
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

        $events = $this->service->forCustomer($customer, $viewer);

        $this->assertTrue($events->contains(fn ($event) => $event->type === 'customer_created'));
        $this->assertFalse($events->contains(fn ($event) => $event->type === 'notes' && $event->summary === 'Secret note'));
        $this->assertFalse($events->contains(fn ($event) => $event->type === 'lead_converted'));
    }

    public function test_customer_without_source_lead_still_shows_creation_event(): void
    {
        $user = User::factory()->create();

        $customer = Customer::factory()->create([
            'created_by' => $user->id,
            'source_lead_id' => null,
            'name' => 'Standalone Customer',
        ]);

        Task::factory()->assignedTo($user)->create([
            'created_by' => $user->id,
            'customer_id' => $customer->id,
            'title' => 'Onboarding call',
        ]);

        $events = $this->service->forCustomer($customer, $user);

        $this->assertCount(2, $events);
        $this->assertTrue($events->contains(fn ($event) => $event->type === 'customer_created'));
        $this->assertTrue($events->contains(fn ($event) => $event->type === 'task_created'));
    }
}
