<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CsvExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    public function test_sales_user_can_export_filtered_leads_and_respects_visibility(): void
    {
        $viewer = User::factory()->create(['name' => 'Viewer Sales']);
        $other = User::factory()->create(['name' => 'Other Sales']);

        Lead::factory()->assignedTo($viewer)->create([
            'created_by' => $viewer->id,
            'name' => 'Visible Export Lead',
            'status' => 'new',
            'source' => 'website',
        ]);

        Lead::factory()->assignedTo($viewer)->create([
            'created_by' => $viewer->id,
            'name' => 'Other Status Lead',
            'status' => 'won',
            'source' => 'website',
        ]);

        Lead::factory()->assignedTo($other)->create([
            'created_by' => $other->id,
            'name' => 'Hidden Lead',
            'status' => 'new',
            'source' => 'website',
        ]);

        $response = $this->actingAs($viewer)
            ->get(route('exports.leads', ['status' => 'new', 'source' => 'website']));

        $response->assertOk();
        $response->assertHeader('content-disposition');

        $csv = $response->streamedContent();

        $this->assertStringContainsString('Name,Email,Phone,Company,Source,Status,"Assigned To","Created At"', $csv);
        $this->assertStringContainsString('Visible Export Lead', $csv);
        $this->assertStringNotContainsString('Other Status Lead', $csv);
        $this->assertStringNotContainsString('Hidden Lead', $csv);
    }

    public function test_customer_export_respects_status_filter(): void
    {
        $user = User::factory()->create();

        Customer::factory()->create([
            'created_by' => $user->id,
            'name' => 'Active Export Customer',
            'status' => 'active',
        ]);

        Customer::factory()->create([
            'created_by' => $user->id,
            'name' => 'Inactive Export Customer',
            'status' => 'inactive',
        ]);

        $response = $this->actingAs($user)
            ->get(route('exports.customers', ['status' => 'active']));

        $response->assertOk();

        $csv = $response->streamedContent();

        $this->assertStringContainsString('Name,Email,Phone,Company,Status,"Created At"', $csv);
        $this->assertStringContainsString('Active Export Customer', $csv);
        $this->assertStringNotContainsString('Inactive Export Customer', $csv);
    }

    public function test_task_export_respects_filters_and_visibility(): void
    {
        $viewer = User::factory()->create();
        $other = User::factory()->create();

        $lead = Lead::factory()->assignedTo($viewer)->create([
            'created_by' => $viewer->id,
            'name' => 'Related Lead Name',
        ]);

        Task::factory()->assignedTo($viewer)->create([
            'created_by' => $viewer->id,
            'lead_id' => $lead->id,
            'title' => 'Visible High Task',
            'status' => 'pending',
            'priority' => 'high',
        ]);

        Task::factory()->assignedTo($viewer)->create([
            'created_by' => $viewer->id,
            'title' => 'Low Priority Task',
            'status' => 'pending',
            'priority' => 'low',
        ]);

        Task::factory()->assignedTo($other)->create([
            'created_by' => $other->id,
            'title' => 'Other User Task',
            'status' => 'pending',
            'priority' => 'high',
        ]);

        $response = $this->actingAs($viewer)
            ->get(route('exports.tasks', ['priority' => 'high', 'status' => 'pending']));

        $response->assertOk();

        $csv = $response->streamedContent();

        $this->assertStringContainsString('Title,Status,Priority,"Due Date","Assigned To","Related Type","Related Name","Created At"', $csv);
        $this->assertStringContainsString('Visible High Task', $csv);
        $this->assertStringContainsString('Lead', $csv);
        $this->assertStringContainsString('Related Lead Name', $csv);
        $this->assertStringNotContainsString('Low Priority Task', $csv);
        $this->assertStringNotContainsString('Other User Task', $csv);
    }

    public function test_index_pages_show_export_button(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($user)
            ->get(route('leads.index'))
            ->assertOk()
            ->assertSee('Export CSV')
            ->assertSee(route('exports.leads'), false);

        $this->actingAs($user)
            ->get(route('customers.index'))
            ->assertOk()
            ->assertSee('Export CSV');

        $this->actingAs($user)
            ->get(route('tasks.index'))
            ->assertOk()
            ->assertSee('Export CSV');

        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('Export CSV')
            ->assertSee(route('exports.users'), false);
    }

    public function test_admin_can_export_filtered_users(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Admin Exporter']);
        $activeSales = User::factory()->create([
            'name' => 'Active Sales Export',
            'status' => 'active',
        ]);
        User::factory()->create([
            'name' => 'Inactive Sales Export',
            'status' => 'inactive',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('exports.users', [
                'status' => 'active',
                'role' => 'sales',
                'search' => 'Active Sales',
            ]));

        $response->assertOk();

        $csv = $response->streamedContent();

        $this->assertStringContainsString('Name,Email,Role,Status,"Created At"', $csv);
        $this->assertStringContainsString('Active Sales Export', $csv);
        $this->assertStringContainsString($activeSales->email, $csv);
        $this->assertStringNotContainsString('Inactive Sales Export', $csv);
        $this->assertStringNotContainsString('Admin Exporter', $csv);
    }

    public function test_sales_user_cannot_export_users(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('exports.users'))
            ->assertForbidden();
    }

    public function test_user_without_view_permission_cannot_export(): void
    {
        $user = User::factory()->create();
        $salesRole = \App\Models\Role::query()->where('slug', 'sales')->firstOrFail();
        $salesRole->permissions()->detach();
        $user->cachedPermissionSlugs = null;

        $this->actingAs($user)->get(route('exports.leads'))->assertForbidden();
        $this->actingAs($user)->get(route('exports.customers'))->assertForbidden();
        $this->actingAs($user)->get(route('exports.tasks'))->assertForbidden();
        $this->actingAs($user)->get(route('exports.users'))->assertForbidden();
    }

    public function test_guest_is_redirected_from_exports(): void
    {
        $this->get(route('exports.leads'))->assertRedirect(route('login'));
        $this->get(route('exports.customers'))->assertRedirect(route('login'));
        $this->get(route('exports.tasks'))->assertRedirect(route('login'));
        $this->get(route('exports.users'))->assertRedirect(route('login'));
    }

    public function test_empty_export_still_returns_headers(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('exports.leads', ['status' => 'lost']));

        $response->assertOk();
        $this->assertStringContainsString('Name,Email,Phone,Company,Source,Status,"Assigned To","Created At"', $response->streamedContent());
    }
}
