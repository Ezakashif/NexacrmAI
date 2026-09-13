<?php

namespace Tests\Unit\Services;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Task;
use App\Models\User;
use App\Notifications\CustomerCreated;
use App\Notifications\LeadAssigned;
use App\Notifications\TaskAssigned;
use App\Notifications\WebsiteLeadReceived;
use App\Services\CrmNotificationDispatcher;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CrmNotificationDispatcherTest extends TestCase
{
    use RefreshDatabase;

    private CrmNotificationDispatcher $dispatcher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
        $this->dispatcher = app(CrmNotificationDispatcher::class);
        Notification::fake();
    }

    public function test_lead_assigned_notifies_changed_non_self_assignee(): void
    {
        $actor = User::factory()->admin()->create();
        $assignee = User::factory()->create(['company_id' => $actor->company_id]);
        $lead = Lead::factory()->create([
            'company_id' => $actor->company_id,
            'created_by' => $actor->id,
            'assigned_to' => $assignee->id,
        ]);

        $this->dispatcher->leadAssigned($lead, $actor->id);

        Notification::assertSentTo($assignee, LeadAssigned::class);
        Notification::assertNotSentTo($actor, LeadAssigned::class);
    }

    public function test_lead_assigned_skips_self_assignment(): void
    {
        $actor = User::factory()->admin()->create();
        $lead = Lead::factory()->create([
            'company_id' => $actor->company_id,
            'created_by' => $actor->id,
            'assigned_to' => $actor->id,
        ]);

        $this->dispatcher->leadAssigned($lead, $actor->id);

        Notification::assertNothingSent();
    }

    public function test_lead_assigned_skips_when_assignee_or_company_missing(): void
    {
        $actor = User::factory()->admin()->create();
        $assignee = User::factory()->create(['company_id' => $actor->company_id]);

        $leadWithoutAssignee = Lead::factory()->create([
            'company_id' => $actor->company_id,
            'created_by' => $actor->id,
            'assigned_to' => null,
        ]);

        $this->dispatcher->leadAssigned($leadWithoutAssignee, $actor->id);
        Notification::assertNothingSent();

        $lead = Lead::factory()->create([
            'company_id' => $actor->company_id,
            'created_by' => $actor->id,
            'assigned_to' => $assignee->id,
        ]);
        $lead->company_id = null;

        $this->dispatcher->leadAssigned($lead, $actor->id);
        Notification::assertNothingSent();
    }

    public function test_task_assigned_notifies_changed_non_self_assignee(): void
    {
        $actor = User::factory()->admin()->create();
        $assignee = User::factory()->create(['company_id' => $actor->company_id]);
        $task = Task::factory()->create([
            'company_id' => $actor->company_id,
            'created_by' => $actor->id,
            'assigned_to' => $assignee->id,
        ]);

        $this->dispatcher->taskAssigned($task, $actor->id);

        Notification::assertSentTo($assignee, TaskAssigned::class);
        Notification::assertNotSentTo($actor, TaskAssigned::class);
    }

    public function test_task_assigned_skips_self_assignment(): void
    {
        $actor = User::factory()->admin()->create();
        $task = Task::factory()->create([
            'company_id' => $actor->company_id,
            'created_by' => $actor->id,
            'assigned_to' => $actor->id,
        ]);

        $this->dispatcher->taskAssigned($task, $actor->id);

        Notification::assertNothingSent();
    }

    public function test_customer_created_notifies_only_active_company_admins(): void
    {
        $creator = User::factory()->admin()->create();
        $otherAdmin = User::factory()->admin()->create(['company_id' => $creator->company_id]);
        $inactiveAdmin = User::factory()->admin()->inactive()->create(['company_id' => $creator->company_id]);
        $otherCompany = Company::factory()->create();
        $otherCompanyAdmin = User::factory()->admin()->create(['company_id' => $otherCompany->id]);

        $customer = Customer::factory()->create(['company_id' => $creator->company_id]);

        $this->dispatcher->customerCreated($customer);

        Notification::assertSentTo($creator, CustomerCreated::class);
        Notification::assertSentTo($otherAdmin, CustomerCreated::class);
        Notification::assertNotSentTo($inactiveAdmin, CustomerCreated::class);
        Notification::assertNotSentTo($otherCompanyAdmin, CustomerCreated::class);
    }

    public function test_website_lead_received_notifies_active_company_admins(): void
    {
        $admin = User::factory()->admin()->create();
        $secondAdmin = User::factory()->admin()->create(['company_id' => $admin->company_id]);
        $inactiveAdmin = User::factory()->admin()->inactive()->create(['company_id' => $admin->company_id]);
        $lead = Lead::factory()->create([
            'company_id' => $admin->company_id,
            'created_by' => $admin->id,
            'assigned_to' => $admin->id,
        ]);

        $this->dispatcher->websiteLeadReceived($lead);

        Notification::assertSentTo($admin, WebsiteLeadReceived::class);
        Notification::assertSentTo($secondAdmin, WebsiteLeadReceived::class);
        Notification::assertNotSentTo($inactiveAdmin, WebsiteLeadReceived::class);
    }

    public function test_website_lead_received_skips_when_company_missing(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = Lead::factory()->create([
            'company_id' => $admin->company_id,
            'created_by' => $admin->id,
            'assigned_to' => $admin->id,
        ]);
        $lead->company_id = null;

        $this->dispatcher->websiteLeadReceived($lead);

        Notification::assertNothingSent();
    }

    public function test_inactive_assignee_does_not_receive_lead_assignment_notification(): void
    {
        $actor = User::factory()->admin()->create();
        $inactiveAssignee = User::factory()->inactive()->create(['company_id' => $actor->company_id]);
        $lead = Lead::factory()->create([
            'company_id' => $actor->company_id,
            'created_by' => $actor->id,
            'assigned_to' => $inactiveAssignee->id,
        ]);

        $this->dispatcher->leadAssigned($lead, $actor->id);

        Notification::assertNothingSent();
    }
}
