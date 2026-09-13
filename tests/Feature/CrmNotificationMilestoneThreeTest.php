<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Lead;
use App\Models\Task;
use App\Models\User;
use App\Notifications\CustomerCreated;
use App\Notifications\LeadAssigned;
use App\Notifications\TaskAssigned;
use App\Notifications\WebsiteLeadReceived;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmNotificationMilestoneThreeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
        config([
            'website_leads.webhook_secret' => 'notification-test-secret',
            'website_leads.created_by_email' => null,
        ]);
    }

    public function test_lead_assignment_notifies_only_a_changed_non_self_assignee(): void
    {
        $actor = User::factory()->admin()->create();
        $assignee = User::factory()->create(['company_id' => $actor->company_id]);
        $lead = Lead::factory()->create([
            'company_id' => $actor->company_id,
            'created_by' => $actor->id,
            'assigned_to' => $actor->id,
            'status' => 'new',
        ]);

        $this->actingAs($actor)->put(route('leads.update', $lead), [
            'name' => $lead->name,
            'status' => $lead->status,
            'assigned_to' => $assignee->id,
        ])->assertRedirect(route('leads.show', $lead));

        $this->assertSame(1, $assignee->notifications()->count());
        $notification = $assignee->notifications()->firstOrFail();
        $this->assertSame(LeadAssigned::class, $notification->type);
        $this->assertSame(route('leads.show', $lead, false), $notification->data['url']);
        $this->assertSame(0, $actor->notifications()->count());

        $this->actingAs($actor)->put(route('leads.update', $lead), [
            'name' => $lead->name,
            'status' => $lead->status,
            'assigned_to' => $assignee->id,
        ])->assertRedirect(route('leads.show', $lead));

        $this->assertSame(1, $assignee->notifications()->count());
    }

    public function test_task_assignment_notifies_only_when_the_assignee_changes(): void
    {
        $actor = User::factory()->admin()->create();
        $assignee = User::factory()->create(['company_id' => $actor->company_id]);
        $task = Task::factory()->create([
            'company_id' => $actor->company_id,
            'created_by' => $actor->id,
            'assigned_to' => $actor->id,
        ]);

        $this->actingAs($actor)->put(route('tasks.update', $task), [
            'title' => $task->title,
            'priority' => $task->priority,
            'status' => $task->status,
            'assigned_to' => $assignee->id,
        ])->assertRedirect(route('tasks.index'));

        $this->assertSame(1, $assignee->notifications()->count());
        $this->assertSame(TaskAssigned::class, $assignee->notifications()->firstOrFail()->type);

        $this->actingAs($actor)->put(route('tasks.update', $task), [
            'title' => $task->title,
            'priority' => $task->priority,
            'status' => $task->status,
            'assigned_to' => $assignee->id,
        ])->assertRedirect(route('tasks.index'));

        $this->assertSame(1, $assignee->notifications()->count());
    }

    public function test_customer_created_notifies_only_active_administrators_in_its_company(): void
    {
        $creator = User::factory()->admin()->create();
        $otherAdmin = User::factory()->admin()->create(['company_id' => $creator->company_id]);
        $inactiveAdmin = User::factory()->admin()->inactive()->create(['company_id' => $creator->company_id]);
        $otherCompany = Company::factory()->create();
        $otherCompanyAdmin = User::factory()->create([
            'company_id' => $otherCompany->id,
            'role' => 'admin',
        ]);

        $this->actingAs($creator)->post(route('customers.store'), [
            'name' => 'Notification Customer',
        ])->assertRedirect(route('customers.index'));

        $this->assertSame(CustomerCreated::class, $creator->notifications()->firstOrFail()->type);
        $this->assertSame(CustomerCreated::class, $otherAdmin->notifications()->firstOrFail()->type);
        $this->assertSame(0, $inactiveAdmin->notifications()->count());
        $this->assertSame(0, $otherCompanyAdmin->notifications()->count());
    }

    public function test_website_lead_notifies_active_company_administrators(): void
    {
        $admin = User::factory()->admin()->create();
        $secondAdmin = User::factory()->admin()->create(['company_id' => $admin->company_id]);
        $inactiveAdmin = User::factory()->admin()->inactive()->create(['company_id' => $admin->company_id]);

        $this->postJson(route('webhooks.leads.website'), [
            'name' => 'Website Notification Lead',
            'email' => 'lead@example.com',
        ], [
            'Authorization' => 'Bearer notification-test-secret',
        ])->assertCreated();

        $this->assertSame(WebsiteLeadReceived::class, $admin->notifications()->firstOrFail()->type);
        $this->assertSame(WebsiteLeadReceived::class, $secondAdmin->notifications()->firstOrFail()->type);
        $this->assertSame(0, $inactiveAdmin->notifications()->count());
    }

    public function test_lead_conversion_notifies_active_company_administrators_of_the_customer(): void
    {
        $admin = User::factory()->admin()->create();
        $secondAdmin = User::factory()->admin()->create(['company_id' => $admin->company_id]);
        $lead = Lead::factory()->create([
            'company_id' => $admin->company_id,
            'created_by' => $admin->id,
            'assigned_to' => $admin->id,
            'status' => 'qualified',
        ]);

        $this->actingAs($admin)
            ->post(route('leads.convert', $lead))
            ->assertRedirect();

        $this->assertSame(CustomerCreated::class, $admin->notifications()->firstOrFail()->type);
        $this->assertSame(CustomerCreated::class, $secondAdmin->notifications()->firstOrFail()->type);
    }
}
