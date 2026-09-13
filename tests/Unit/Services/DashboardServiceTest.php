<?php

namespace Tests\Unit\Services;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use App\Services\DashboardService;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    private DashboardService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
        $this->service = app(DashboardService::class);
    }

    public function test_for_user_returns_expected_payload_shape(): void
    {
        $user = User::factory()->create();

        $payload = $this->service->forUser($user);

        $expectedKeys = [
            'canViewLeads',
            'canViewTasks',
            'canViewCustomers',
            'canViewActivityLogs',
            'canViewAllLeadAnalytics',
            'customerCount',
            'leadCount',
            'taskCount',
            'todaysFollowUpsCount',
            'pendingTasksCount',
            'overdueTasksCount',
            'newLeadsCount',
            'wonLeadsCount',
            'lostLeadsCount',
            'conversionRate',
            'todaysFollowUps',
            'pendingTasks',
            'overdueTasks',
            'recentLeads',
            'recentCustomers',
            'recentActivities',
            'monthlyLeadGrowth',
            'leadSourceDistribution',
            'quickActions',
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $payload, "Missing key: {$key}");
        }

        $this->assertIsArray($payload['monthlyLeadGrowth']);
        $this->assertArrayHasKey('labels', $payload['monthlyLeadGrowth']);
        $this->assertArrayHasKey('data', $payload['monthlyLeadGrowth']);
    }

    public function test_sales_user_sees_only_assigned_leads_and_tasks(): void
    {
        $viewer = User::factory()->create(['name' => 'Viewer Sales']);
        $other = User::factory()->create(['name' => 'Other Sales']);

        Lead::factory()->assignedTo($viewer)->create([
            'status' => 'new',
            'follow_up_date' => today(),
            'source' => 'website',
        ]);
        Lead::factory()->assignedTo($other)->create([
            'status' => 'won',
            'source' => 'referral',
        ]);

        Task::factory()->assignedTo($viewer)->create([
            'status' => 'pending',
            'due_date' => now()->subDay(),
            'title' => 'Viewer overdue task',
        ]);
        Task::factory()->assignedTo($other)->create([
            'status' => 'pending',
            'due_date' => now()->subDay(),
            'title' => 'Other overdue task',
        ]);

        $payload = $this->service->forUser($viewer);

        $this->assertSame(1, $payload['leadCount']);
        $this->assertSame(1, $payload['taskCount']);
        $this->assertSame(1, $payload['todaysFollowUpsCount']);
        $this->assertSame(1, $payload['pendingTasksCount']);
        $this->assertSame(1, $payload['overdueTasksCount']);
        $this->assertFalse($payload['canViewAllLeadAnalytics']);
        $this->assertSame('Viewer overdue task', $payload['overdueTasks']->first()->title);
    }

    public function test_admin_sees_all_company_leads_and_can_filter_analytics(): void
    {
        $admin = User::factory()->admin()->create();
        $sales = User::factory()->create();

        Lead::factory()->assignedTo($admin)->create(['status' => 'new']);
        Lead::factory()->assignedTo($sales)->create(['status' => 'won']);
        Lead::factory()->assignedTo($sales)->create(['status' => 'lost']);

        $payload = $this->service->forUser($admin);

        $this->assertSame(3, $payload['leadCount']);
        $this->assertTrue($payload['canViewAllLeadAnalytics']);
        $this->assertSame(50.0, $payload['conversionRate']);
    }

    public function test_user_without_module_permissions_receives_null_counts_and_empty_lists(): void
    {
        $user = User::factory()->create();
        $emptyRole = Role::query()->create([
            'company_id' => $user->company_id,
            'slug' => 'empty-dashboard',
            'name' => 'Empty Dashboard',
            'description' => 'No CRM module access.',
            'is_system' => false,
        ]);
        $user->syncRoles([$emptyRole->id]);

        Customer::factory()->create();
        Lead::factory()->create();
        Task::factory()->create();

        $payload = $this->service->forUser($user->fresh());

        $this->assertFalse($payload['canViewLeads']);
        $this->assertFalse($payload['canViewTasks']);
        $this->assertFalse($payload['canViewCustomers']);
        $this->assertNull($payload['leadCount']);
        $this->assertNull($payload['taskCount']);
        $this->assertNull($payload['customerCount']);
        $this->assertTrue($payload['todaysFollowUps']->isEmpty());
        $this->assertTrue($payload['recentLeads']->isEmpty());
        $this->assertSame(['labels' => [], 'data' => []], $payload['monthlyLeadGrowth']);
    }

    public function test_recent_activities_respects_own_only_permission(): void
    {
        $viewer = User::factory()->create();
        $other = User::factory()->create();

        $lead = Lead::factory()->assignedTo($viewer)->create(['created_by' => $viewer->id]);

        ActivityLog::create([
            'user_id' => $viewer->id,
            'action' => 'lead.created',
            'subject_type' => Lead::class,
            'subject_id' => $lead->id,
            'properties' => ['name' => $lead->name],
            'ip_address' => '127.0.0.1',
        ]);

        ActivityLog::create([
            'user_id' => $other->id,
            'action' => 'lead.created',
            'subject_type' => Lead::class,
            'subject_id' => $lead->id,
            'properties' => ['name' => 'Other activity'],
            'ip_address' => '127.0.0.1',
        ]);

        $payload = $this->service->forUser($viewer);

        $this->assertTrue($payload['canViewActivityLogs']);
        $this->assertCount(1, $payload['recentActivities']);
        $this->assertSame($viewer->id, $payload['recentActivities']->first()->user_id);
    }

    public function test_quick_actions_reflect_create_permissions(): void
    {
        $user = User::factory()->create();

        $actions = $this->service->forUser($user)['quickActions'];

        $labels = collect($actions)->pluck('label')->all();

        $this->assertContains('Add Lead', $labels);
        $this->assertContains('Lead Board', $labels);
        $this->assertNotContains('Add Task', $labels);
    }
}
