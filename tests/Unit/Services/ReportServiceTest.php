<?php

namespace Tests\Unit\Services;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use App\Services\ReportService;
use Carbon\Carbon;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportServiceTest extends TestCase
{
    use RefreshDatabase;

    private ReportService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
        $this->service = app(ReportService::class);
    }

    public function test_for_user_returns_visibility_flags_and_report_sections(): void
    {
        $user = User::factory()->create();

        $payload = $this->service->forUser($user, $this->defaultFilters());

        $this->assertTrue($payload['canViewLeads']);
        $this->assertTrue($payload['canViewTasks']);
        $this->assertTrue($payload['canViewCustomers']);
        $this->assertTrue($payload['canExport']);
        $this->assertFalse($payload['canFilterEmployees']);
        $this->assertIsArray($payload['leads']);
        $this->assertIsArray($payload['tasks']);
        $this->assertIsArray($payload['customers']);
        $this->assertIsArray($payload['performance']);
    }

    public function test_sales_user_only_sees_assigned_leads_in_reports(): void
    {
        $viewer = User::factory()->create(['name' => 'Viewer Sales']);
        $other = User::factory()->create(['name' => 'Other Sales']);

        Lead::factory()->assignedTo($viewer)->create([
            'status' => 'won',
            'source' => 'website',
            'created_at' => now(),
        ]);
        Lead::factory()->assignedTo($other)->create([
            'status' => 'won',
            'source' => 'facebook',
            'created_at' => now(),
        ]);

        $payload = $this->service->forUser($viewer, $this->defaultFilters());

        $this->assertSame(1, $payload['leads']['total']);
        $this->assertSame(1, $payload['performance']['leads_assigned']);
        $this->assertTrue(collect($payload['leads']['by_assignee'])->contains('employee', 'Viewer Sales'));
        $this->assertFalse(collect($payload['leads']['by_assignee'])->contains('employee', 'Other Sales'));
    }

    public function test_admin_can_filter_by_employee_and_source(): void
    {
        $admin = User::factory()->admin()->create();
        $sales = User::factory()->create(['name' => 'Filtered Sales']);

        Lead::factory()->assignedTo($sales)->create([
            'created_by' => $admin->id,
            'status' => 'new',
            'source' => 'referral',
            'created_at' => now(),
        ]);
        Lead::factory()->assignedTo($admin)->create([
            'created_by' => $admin->id,
            'status' => 'new',
            'source' => 'website',
            'created_at' => now(),
        ]);

        $payload = $this->service->forUser($admin, array_merge($this->defaultFilters(), [
            'employee_id' => $sales->id,
            'source' => 'referral',
        ]));

        $this->assertTrue($payload['canFilterEmployees']);
        $this->assertSame(1, $payload['leads']['total']);
        $this->assertSame(1, $payload['performance']['leads_assigned']);
    }

    public function test_sales_user_employee_filter_is_ignored(): void
    {
        $viewer = User::factory()->create();
        $other = User::factory()->create();

        Lead::factory()->assignedTo($viewer)->create(['created_at' => now()]);
        Lead::factory()->assignedTo($other)->create(['created_at' => now()]);

        $employeeId = $this->invokeResolveEmployeeFilter($viewer, $other->id, 'leads');

        $this->assertNull($employeeId);
    }

    public function test_admin_employee_filter_ignores_foreign_company_user(): void
    {
        $admin = User::factory()->admin()->create();
        $foreign = User::factory()->create(['company_id' => Company::factory()->create()->id]);

        $employeeId = $this->invokeResolveEmployeeFilter($admin, $foreign->id, 'leads');

        $this->assertNull($employeeId);
    }

    public function test_status_filter_limits_lead_report_but_not_performance_conversion(): void
    {
        $admin = User::factory()->admin()->create();

        Lead::factory()->assignedTo($admin)->create([
            'status' => 'new',
            'created_at' => now(),
        ]);
        Lead::factory()->assignedTo($admin)->create([
            'status' => 'won',
            'created_at' => now(),
        ]);

        $payload = $this->service->forUser($admin, array_merge($this->defaultFilters(), [
            'status' => 'new',
        ]));

        $this->assertSame(1, $payload['leads']['total']);
        $this->assertSame(2, $payload['performance']['leads_assigned']);
        $this->assertSame(1, $payload['performance']['leads_converted']);
    }

    public function test_user_without_report_permissions_gets_null_sections(): void
    {
        $user = User::factory()->create();
        $restricted = Role::query()->create([
            'company_id' => $user->company_id,
            'slug' => 'no-reports',
            'name' => 'No Reports',
            'description' => 'Cannot view reports.',
            'is_system' => false,
        ]);
        $user->syncRoles([$restricted->id]);

        Customer::factory()->create(['created_at' => now()]);
        Lead::factory()->create(['created_at' => now()]);
        Task::factory()->create(['created_at' => now()]);

        $payload = $this->service->forUser($user->fresh(), $this->defaultFilters());

        $this->assertFalse($payload['canViewLeads']);
        $this->assertFalse($payload['canExport']);
        $this->assertNull($payload['leads']);
        $this->assertNull($payload['tasks']);
        $this->assertNull($payload['customers']);
        $this->assertNull($payload['performance']);
    }

    public function test_filtered_leads_query_respects_date_range(): void
    {
        $user = User::factory()->admin()->create();

        Lead::factory()->assignedTo($user)->create(['created_at' => now()->subMonths(2)]);
        Lead::factory()->assignedTo($user)->create(['created_at' => now()]);

        $dateFrom = Carbon::parse(now()->startOfMonth()->toDateString())->startOfDay();
        $dateTo = Carbon::parse(now()->toDateString())->endOfDay();

        $count = $this->service
            ->filteredLeadsQuery($user, $this->defaultFilters(), $dateFrom, $dateTo)
            ->count();

        $this->assertSame(1, $count);
    }

    /**
     * @return array{
     *     date_from: string,
     *     date_to: string,
     *     employee_id: int|null,
     *     source: string|null,
     *     status: string|null
     * }
     */
    private function defaultFilters(): array
    {
        return [
            'date_from' => now()->startOfMonth()->toDateString(),
            'date_to' => now()->toDateString(),
            'employee_id' => null,
            'source' => null,
            'status' => null,
        ];
    }

    private function invokeResolveEmployeeFilter(User $user, ?int $employeeId, string $module): ?int
    {
        $reflection = new \ReflectionMethod($this->service, 'resolveEmployeeFilter');
        $reflection->setAccessible(true);

        return $reflection->invoke($this->service, $user, $employeeId, $module);
    }
}
