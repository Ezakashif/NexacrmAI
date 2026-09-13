<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Task;
use App\Models\User;
use App\Services\DashboardService;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    public function test_authenticated_user_can_view_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Dashboard');
        $response->assertSee('What needs your attention today');
    }

    public function test_dashboard_shows_actionable_metrics_for_sales_user(): void
    {
        $user = User::factory()->create();

        Lead::factory()->assignedTo($user)->create([
            'created_by' => $user->id,
            'name' => 'Assigned Follow-up Lead',
            'status' => 'new',
            'follow_up_date' => today(),
            'source' => 'website',
        ]);

        Lead::factory()->assignedTo($user)->create([
            'created_by' => $user->id,
            'status' => 'won',
            'source' => 'referral',
        ]);

        Lead::factory()->assignedTo($user)->create([
            'created_by' => $user->id,
            'status' => 'lost',
            'source' => 'facebook',
        ]);

        Task::factory()->assignedTo($user)->create([
            'created_by' => $user->id,
            'status' => 'pending',
            'due_date' => now()->subDay(),
            'title' => 'Call overdue prospect',
        ]);

        Customer::factory()->create([
            'created_by' => $user->id,
            'name' => 'Acme Recent Customer',
        ]);

        $loggedLead = Lead::factory()->assignedTo($user)->create([
            'created_by' => $user->id,
            'name' => 'Logged Activity Lead',
            'status' => 'contacted',
        ]);

        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'lead.created',
            'subject_type' => Lead::class,
            'subject_id' => $loggedLead->id,
            'properties' => ['name' => $loggedLead->name],
            'ip_address' => '127.0.0.1',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Follow-ups Today');
        $response->assertSee('Pending Tasks');
        $response->assertSee('Overdue Tasks');
        $response->assertSee('New leads');
        $response->assertSee('Won leads');
        $response->assertSee('Lost leads');
        $response->assertSee('Lead Conversion Rate');
        $response->assertSee('Monthly lead growth');
        $response->assertSee('Lead source distribution');
        $response->assertSee('Recent leads');
        $response->assertSee('Recent customers');
        $response->assertSee('Recent activities');
        $response->assertSee('Assigned Follow-up Lead');
        $response->assertSee('Call overdue prospect');
        $response->assertSee('Acme Recent Customer');
        $response->assertSee('Add Lead');
        $response->assertSee('Add Customer');
        $response->assertDontSee('Add Task');
        $response->assertSee('50.0');
        $response->assertSee('crm-page-header', false);
    }

    public function test_sales_user_only_sees_assigned_lead_and_task_analytics(): void
    {
        $viewer = User::factory()->create();
        $other = User::factory()->create();

        Lead::factory()->assignedTo($viewer)->create([
            'created_by' => $viewer->id,
            'name' => 'My Assigned Lead',
            'status' => 'new',
            'follow_up_date' => today(),
            'source' => 'website',
        ]);

        Lead::factory()->assignedTo($other)->create([
            'created_by' => $other->id,
            'name' => 'Other Persons Lead',
            'status' => 'new',
            'follow_up_date' => today(),
            'source' => 'facebook',
        ]);

        Lead::factory()->create([
            'created_by' => $other->id,
            'assigned_to' => null,
            'name' => 'Unassigned Lead',
            'status' => 'won',
            'source' => 'referral',
        ]);

        Task::factory()->assignedTo($other)->create([
            'created_by' => $other->id,
            'status' => 'pending',
            'title' => 'Hidden other task',
            'due_date' => now()->subDays(2),
        ]);

        Task::factory()->assignedTo($viewer)->create([
            'created_by' => $viewer->id,
            'status' => 'pending',
            'title' => 'Visible own task',
            'due_date' => now()->subDay(),
        ]);

        $payload = app(DashboardService::class)->forUser($viewer);

        $this->assertFalse($payload['canViewAllLeadAnalytics']);
        $this->assertSame(1, $payload['todaysFollowUpsCount']);
        $this->assertSame(1, $payload['newLeadsCount']);
        $this->assertSame(0, $payload['wonLeadsCount']);
        $this->assertSame(1, $payload['leadCount']);
        $this->assertSame(1, $payload['pendingTasksCount']);
        $this->assertSame(1, $payload['overdueTasksCount']);
        $this->assertTrue($payload['recentLeads']->contains('name', 'My Assigned Lead'));
        $this->assertFalse($payload['recentLeads']->contains('name', 'Other Persons Lead'));
        $this->assertFalse($payload['recentLeads']->contains('name', 'Unassigned Lead'));
        $this->assertSame(1, array_sum($payload['monthlyLeadGrowth']['data']));
        $this->assertSame(1, $payload['leadSourceDistribution']['data'][array_search('Website', $payload['leadSourceDistribution']['labels'], true)]);

        $response = $this->actingAs($viewer)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('My Assigned Lead');
        $response->assertSee('Visible own task');
        $response->assertDontSee('Other Persons Lead');
        $response->assertDontSee('Unassigned Lead');
        $response->assertDontSee('Hidden other task');
    }

    public function test_admin_sees_all_lead_and_task_analytics(): void
    {
        $admin = User::factory()->admin()->create();
        $sales = User::factory()->create();

        Lead::factory()->assignedTo($sales)->create([
            'created_by' => $sales->id,
            'name' => 'Sales Assigned Lead',
            'status' => 'new',
            'follow_up_date' => today(),
            'source' => 'website',
        ]);

        Lead::factory()->create([
            'created_by' => $admin->id,
            'assigned_to' => null,
            'name' => 'Unassigned Admin Lead',
            'status' => 'won',
            'source' => 'referral',
        ]);

        Task::factory()->assignedTo($sales)->create([
            'created_by' => $sales->id,
            'status' => 'pending',
            'title' => 'Sales overdue task',
            'due_date' => now()->subDay(),
        ]);

        $payload = app(DashboardService::class)->forUser($admin);

        $this->assertTrue($payload['canViewAllLeadAnalytics']);
        $this->assertSame(1, $payload['todaysFollowUpsCount']);
        $this->assertSame(1, $payload['newLeadsCount']);
        $this->assertSame(1, $payload['wonLeadsCount']);
        $this->assertSame(2, $payload['leadCount']);
        $this->assertSame(1, $payload['pendingTasksCount']);
        $this->assertSame(1, $payload['overdueTasksCount']);
        $this->assertTrue($payload['recentLeads']->contains('name', 'Sales Assigned Lead'));
        $this->assertTrue($payload['recentLeads']->contains('name', 'Unassigned Admin Lead'));
        $this->assertSame(2, array_sum($payload['monthlyLeadGrowth']['data']));

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Sales Assigned Lead');
        $response->assertSee('Unassigned Admin Lead');
        $response->assertSee('Sales overdue task');
    }

    public function test_admin_sees_quick_actions_they_can_perform(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Add Lead');
        $response->assertSee('Add Task');
        $response->assertSee('Add Customer');
        $response->assertSee('Lead Board');
        $response->assertSee('Task Board');
    }

    public function test_guest_is_redirected_from_dashboard(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }
}
