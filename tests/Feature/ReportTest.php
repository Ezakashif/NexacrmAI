<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    public function test_sales_user_can_view_reports_page(): void
    {
        $user = User::factory()->create();

        Lead::factory()->assignedTo($user)->create([
            'created_by' => $user->id,
            'status' => 'new',
            'source' => 'website',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('reports.index'));

        $response->assertOk();
        $response->assertSee('Reports');
        $response->assertSee('Leads');
        $response->assertSee('Tasks');
        $response->assertSee('Sales Performance');
        $response->assertSee('Export CSV');
    }

    public function test_sales_user_only_sees_assigned_lead_metrics(): void
    {
        $viewer = User::factory()->create(['name' => 'Viewer Sales']);
        $other = User::factory()->create(['name' => 'Other Sales']);

        Lead::factory()->assignedTo($viewer)->create([
            'created_by' => $viewer->id,
            'status' => 'won',
            'source' => 'website',
            'created_at' => now(),
        ]);

        Lead::factory()->assignedTo($other)->create([
            'created_by' => $other->id,
            'status' => 'won',
            'source' => 'facebook',
            'created_at' => now(),
        ]);

        $payload = app(\App\Services\ReportService::class)->forUser($viewer, [
            'date_from' => now()->startOfMonth()->toDateString(),
            'date_to' => now()->toDateString(),
            'employee_id' => null,
            'source' => null,
            'status' => null,
        ]);

        $this->assertSame(1, $payload['leads']['total']);
        $this->assertSame(1, $payload['performance']['leads_assigned']);
        $this->assertSame(1, $payload['performance']['leads_converted']);
        $this->assertTrue(collect($payload['leads']['by_assignee'])->contains('employee', 'Viewer Sales'));
        $this->assertFalse(collect($payload['leads']['by_assignee'])->contains('employee', 'Other Sales'));
    }

    public function test_admin_can_filter_by_employee_and_export_csv(): void
    {
        $admin = User::factory()->admin()->create();
        $sales = User::factory()->create(['name' => 'Sales Export User']);

        Lead::factory()->assignedTo($sales)->create([
            'created_by' => $admin->id,
            'name' => 'Exportable Lead',
            'status' => 'new',
            'source' => 'referral',
            'created_at' => now(),
        ]);

        Customer::factory()->create([
            'created_by' => $admin->id,
            'name' => 'Exportable Customer',
            'created_at' => now(),
        ]);

        Task::factory()->assignedTo($sales)->create([
            'created_by' => $admin->id,
            'title' => 'Exportable Task',
            'status' => 'pending',
            'created_at' => now(),
        ]);

        $filters = [
            'date_from' => now()->startOfMonth()->toDateString(),
            'date_to' => now()->toDateString(),
            'employee_id' => $sales->id,
        ];

        $this->actingAs($admin)
            ->get(route('reports.index', $filters))
            ->assertOk()
            ->assertSee('Sales Export User')
            ->assertSee('Reports');

        $leadsCsv = $this->actingAs($admin)
            ->get(route('reports.export', ['type' => 'leads'] + $filters));
        $leadsCsv->assertOk();
        $this->assertStringContainsString('Exportable Lead', $leadsCsv->streamedContent());

        $customersCsv = $this->actingAs($admin)
            ->get(route('reports.export', ['type' => 'customers'] + $filters));
        $customersCsv->assertOk();
        $this->assertStringContainsString('Exportable Customer', $customersCsv->streamedContent());

        $tasksCsv = $this->actingAs($admin)
            ->get(route('reports.export', ['type' => 'tasks'] + $filters));
        $tasksCsv->assertOk();
        $this->assertStringContainsString('Exportable Task', $tasksCsv->streamedContent());

        $performanceCsv = $this->actingAs($admin)
            ->get(route('reports.export', ['type' => 'performance'] + $filters));
        $performanceCsv->assertOk();
        $this->assertStringContainsString('Conversion Rate', $performanceCsv->streamedContent());
    }

    public function test_user_with_crm_view_but_without_reports_permission_can_access_reports(): void
    {
        $user = User::factory()->create();
        $salesRole = \App\Models\Role::query()->where('slug', 'sales')->firstOrFail();
        $viewReports = \App\Models\Permission::query()->where('slug', 'view.reports')->firstOrFail();
        $exportReports = \App\Models\Permission::query()->where('slug', 'export.reports')->firstOrFail();

        $salesRole->permissions()->detach([$viewReports->id, $exportReports->id]);
        $user->cachedPermissionSlugs = null;

        $this->assertFalse($user->hasPermission('view.reports'));
        $this->assertTrue($user->canAccessReports());

        $this->actingAs($user)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSee('Reports');
    }

    public function test_user_without_crm_or_reports_permission_is_forbidden(): void
    {
        $user = User::factory()->create();
        $salesRole = \App\Models\Role::query()->where('slug', 'sales')->firstOrFail();

        $salesRole->permissions()->detach();
        $user->cachedPermissionSlugs = null;

        $this->actingAs($user)
            ->get(route('reports.index'))
            ->assertForbidden();
    }

    public function test_monthly_growth_includes_leads_outside_date_filter(): void
    {
        $admin = User::factory()->admin()->create();

        Lead::factory()->assignedTo($admin)->create([
            'created_by' => $admin->id,
            'status' => 'new',
            'source' => 'website',
            'created_at' => now()->subMonths(2)->startOfMonth()->addDays(3),
        ]);

        $payload = app(\App\Services\ReportService::class)->forUser($admin, [
            'date_from' => now()->toDateString(),
            'date_to' => now()->toDateString(),
            'employee_id' => null,
            'source' => null,
            'status' => null,
        ]);

        $this->assertSame(0, $payload['leads']['total']);
        $this->assertGreaterThan(0, array_sum($payload['leads']['monthly_growth']['data']));
    }

    public function test_export_requires_export_permission(): void
    {
        $user = User::factory()->create();
        $salesRole = \App\Models\Role::query()->where('slug', 'sales')->firstOrFail();
        $exportReports = \App\Models\Permission::query()->where('slug', 'export.reports')->firstOrFail();

        $salesRole->permissions()->detach([$exportReports->id]);
        $user->cachedPermissionSlugs = null;

        $this->actingAs($user)
            ->get(route('reports.export', [
                'type' => 'leads',
                'date_from' => now()->subDays(89)->toDateString(),
                'date_to' => now()->toDateString(),
            ]))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_from_reports(): void
    {
        $this->get(route('reports.index'))->assertRedirect(route('login'));
    }
}
