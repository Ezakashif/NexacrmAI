<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogLinkTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    public function test_admin_sees_clickable_link_to_existing_lead_subject(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = Lead::factory()->assignedTo($admin)->create([
            'created_by' => $admin->id,
            'name' => 'Linkable Lead',
        ]);

        ActivityLog::create([
            'user_id' => $admin->id,
            'action' => 'lead.updated',
            'subject_type' => Lead::class,
            'subject_id' => $lead->id,
            'properties' => ['name' => 'Linkable Lead'],
        ]);

        $this->actingAs($admin)
            ->get(route('activity-logs.index'))
            ->assertOk()
            ->assertSee(route('leads.show', $lead), false)
            ->assertSee('Updated lead Linkable Lead');
    }

    public function test_deleted_subject_is_not_linked(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = Lead::factory()->assignedTo($admin)->create([
            'created_by' => $admin->id,
            'name' => 'Gone Lead',
        ]);

        ActivityLog::create([
            'user_id' => $admin->id,
            'action' => 'lead.deleted',
            'subject_type' => Lead::class,
            'subject_id' => $lead->id,
            'properties' => ['name' => 'Gone Lead'],
        ]);

        $lead->delete();

        $response = $this->actingAs($admin)->get(route('activity-logs.index'));

        $response->assertOk()
            ->assertSee('Deleted lead Gone Lead')
            ->assertDontSee(route('leads.show', $lead), false);
    }

    public function test_sales_user_cannot_link_to_unassigned_lead(): void
    {
        $sales = User::factory()->create();
        $other = User::factory()->create();
        $lead = Lead::factory()->assignedTo($other)->create([
            'created_by' => $other->id,
            'name' => 'Other Lead',
        ]);

        ActivityLog::create([
            'user_id' => $sales->id,
            'action' => 'lead.updated',
            'subject_type' => Lead::class,
            'subject_id' => $lead->id,
            'properties' => ['name' => 'Other Lead'],
        ]);

        $this->actingAs($sales)
            ->get(route('activity-logs.index'))
            ->assertOk()
            ->assertSee('Updated lead Other Lead')
            ->assertDontSee(route('leads.show', $lead), false);
    }

    public function test_sales_profile_activity_links_to_profile_edit(): void
    {
        $sales = User::factory()->create();

        ActivityLog::create([
            'user_id' => $sales->id,
            'action' => 'profile.updated',
            'subject_type' => User::class,
            'subject_id' => $sales->id,
        ]);

        $this->actingAs($sales)
            ->get(route('activity-logs.index'))
            ->assertOk()
            ->assertSee(route('profile.edit'), false)
            ->assertDontSee(route('users.show', $sales), false);
    }

    public function test_subject_show_url_resolves_for_supported_models(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = Customer::factory()->create(['created_by' => $admin->id, 'name' => 'Cust']);
        $task = Task::factory()->assignedTo($admin)->create([
            'created_by' => $admin->id,
            'title' => 'Task Link',
        ]);
        $user = User::factory()->create(['name' => 'Subject User']);

        $this->assertSame(
            route('customers.show', $customer),
            ActivityLog::make([
                'action' => 'customer.updated',
                'subject_type' => Customer::class,
                'subject_id' => $customer->id,
            ])->setRelation('subject', $customer)->subjectShowUrl($admin)
        );

        $this->assertSame(
            route('tasks.show', $task),
            ActivityLog::make([
                'action' => 'task.updated',
                'subject_type' => Task::class,
                'subject_id' => $task->id,
            ])->setRelation('subject', $task)->subjectShowUrl($admin)
        );

        $this->assertSame(
            route('users.show', $user),
            ActivityLog::make([
                'action' => 'user.updated',
                'subject_type' => User::class,
                'subject_id' => $user->id,
            ])->setRelation('subject', $user)->subjectShowUrl($admin)
        );
    }

    public function test_dashboard_recent_activity_links_when_possible(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = Customer::factory()->create([
            'created_by' => $admin->id,
            'name' => 'Dash Customer',
        ]);

        ActivityLog::create([
            'user_id' => $admin->id,
            'action' => 'customer.created',
            'subject_type' => Customer::class,
            'subject_id' => $customer->id,
            'properties' => ['name' => 'Dash Customer'],
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('customers.show', $customer), false)
            ->assertSee('Created customer Dash Customer');
    }
}
