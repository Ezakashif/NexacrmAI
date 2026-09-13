<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\Task;
use App\Models\User;
use App\Services\Analytics\CrmAnalytics;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class ReportService
{
    /**
     * Build the full reports payload for the authenticated user.
     *
     * @param  array{
     *     date_from: string,
     *     date_to: string,
     *     employee_id: int|null,
     *     source: string|null,
     *     status: string|null
     * }  $filters
     * @return array<string, mixed>
     */
    public function forUser(User $user, array $filters): array
    {
        if (app()->environment('testing')) {
            return $this->buildForUser($user, $filters);
        }

        $cacheKey = sprintf(
            'reports:%s:%s:%s',
            $user->company_id ?? 'none',
            $user->id,
            md5(json_encode($filters).'|'.$user->permissionSlugs()->implode(',')),
        );

        return Cache::remember($cacheKey, now()->addSeconds(90), function () use ($user, $filters) {
            return $this->buildForUser($user, $filters);
        });
    }

    /**
     * @param  array{
     *     date_from: string,
     *     date_to: string,
     *     employee_id: int|null,
     *     source: string|null,
     *     status: string|null
     * }  $filters
     * @return array<string, mixed>
     */
    private function buildForUser(User $user, array $filters): array
    {
        $canViewLeads = $user->hasPermission('view.leads');
        $canViewTasks = $user->hasPermission('view.tasks');
        $canViewCustomers = $user->hasPermission('view.customers');
        $canExport = $user->hasPermission('export.reports');
        $canFilterEmployees = $user->canViewAllLeads() || $user->canViewAllTasks();

        $dateFrom = Carbon::parse($filters['date_from'])->startOfDay();
        $dateTo = Carbon::parse($filters['date_to'])->endOfDay();

        $leadQuery = $canViewLeads
            ? $this->filteredLeadsQuery($user, $filters, $dateFrom, $dateTo)
            : null;

        $taskQuery = $canViewTasks
            ? $this->filteredTasksQuery($user, $filters, $dateFrom, $dateTo)
            : null;

        $customerQuery = $canViewCustomers
            ? $this->filteredCustomersQuery($dateFrom, $dateTo)
            : null;

        return [
            'filters' => $filters,
            'canViewLeads' => $canViewLeads,
            'canViewTasks' => $canViewTasks,
            'canViewCustomers' => $canViewCustomers,
            'canExport' => $canExport,
            'canFilterEmployees' => $canFilterEmployees,
            'employees' => $canFilterEmployees
                ? User::active()->orderBy('name')->get(['id', 'name'])
                : collect(),
            'leadStatuses' => Lead::STATUSES,
            'leadSources' => Lead::SOURCES,
            'leads' => $canViewLeads ? $this->leadReport($leadQuery, $user, $filters) : null,
            'customers' => $canViewCustomers ? $this->customerReport($customerQuery, $leadQuery) : null,
            'tasks' => $canViewTasks ? $this->taskReport($taskQuery, $user) : null,
            'performance' => $canViewLeads ? $this->performanceReport($user, $filters, $dateFrom, $dateTo) : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function filteredLeadsQuery(User $user, array $filters, Carbon $dateFrom, Carbon $dateTo): Builder
    {
        $query = Lead::visibleTo($user)
            ->whereBetween('leads.created_at', [$dateFrom, $dateTo]);

        $employeeId = $this->resolveEmployeeFilter($user, $filters['employee_id'] ?? null, 'leads');

        if ($employeeId !== null) {
            $query->where('leads.assigned_to', $employeeId);
        }

        if (filled($filters['source'] ?? null)) {
            $query->where('leads.source', $filters['source']);
        }

        if (filled($filters['status'] ?? null)) {
            $query->where('leads.status', $filters['status']);
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function filteredTasksQuery(User $user, array $filters, Carbon $dateFrom, Carbon $dateTo): Builder
    {
        $query = Task::visibleTo($user)
            ->whereBetween('tasks.created_at', [$dateFrom, $dateTo]);

        $employeeId = $this->resolveEmployeeFilter($user, $filters['employee_id'] ?? null, 'tasks');

        if ($employeeId !== null) {
            $query->where('tasks.assigned_to', $employeeId);
        }

        return $query;
    }

    public function filteredCustomersQuery(Carbon $dateFrom, Carbon $dateTo): Builder
    {
        return Customer::query()->whereBetween('created_at', [$dateFrom, $dateTo]);
    }

    /**
     * Sales users cannot filter by other employees.
     */
    protected function resolveEmployeeFilter(User $user, ?int $employeeId, string $module): ?int
    {
        $canViewAll = $module === 'leads'
            ? $user->canViewAllLeads()
            : $user->canViewAllTasks();

        if (! $canViewAll || $employeeId === null) {
            return null;
        }

        $exists = User::query()
            ->whereKey($employeeId)
            ->where('company_id', $user->company_id)
            ->exists();

        return $exists ? $employeeId : null;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function leadReport(Builder $leadQuery, User $user, array $filters): array
    {
        $statusCounts = (clone $leadQuery)
            ->selectRaw('leads.status, COUNT(*) as aggregate')
            ->groupBy('leads.status')
            ->pluck('aggregate', 'status');

        $byStatus = [];
        foreach (Lead::STATUSES as $key => $label) {
            $byStatus[] = [
                'status' => $key,
                'label' => $label,
                'count' => (int) ($statusCounts[$key] ?? 0),
            ];
        }

        $byAssignee = (clone $leadQuery)
            ->leftJoin('users', function ($join) use ($user) {
                $join->on('leads.assigned_to', '=', 'users.id')
                    ->where('users.company_id', '=', $user->company_id);
            })
            ->selectRaw('leads.assigned_to, COALESCE(users.name, ?) as employee_name, COUNT(*) as aggregate', ['Unassigned'])
            ->groupBy('leads.assigned_to', 'users.name')
            ->orderByDesc('aggregate')
            ->get()
            ->map(fn ($row) => [
                'employee_id' => $row->assigned_to,
                'employee' => $row->employee_name,
                'count' => (int) $row->aggregate,
            ])
            ->all();

        $byDate = (clone $leadQuery)
            ->selectRaw($this->dateExpression('leads.created_at').' as day_key, COUNT(*) as aggregate')
            ->groupBy('day_key')
            ->orderBy('day_key')
            ->pluck('aggregate', 'day_key')
            ->map(fn ($count) => (int) $count)
            ->all();

        return [
            'total' => (int) $statusCounts->sum(),
            'by_status' => $byStatus,
            'by_status_chart' => [
                'labels' => array_column($byStatus, 'label'),
                'data' => array_column($byStatus, 'count'),
            ],
            'by_source_chart' => $this->leadSourceDistribution(clone $leadQuery),
            'by_assignee' => $byAssignee,
            'by_date' => $byDate,
            'monthly_growth' => $this->monthlyLeadGrowth($user, $filters),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function customerReport(?Builder $customerQuery, ?Builder $leadQuery): array
    {
        $total = $customerQuery ? (clone $customerQuery)->count() : 0;

        $newThisMonth = Customer::query()
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();

        // Converted customers = won leads within the filtered lead set / date range.
        $converted = $leadQuery
            ? (clone $leadQuery)->where('status', 'won')->count()
            : 0;

        $byDate = $customerQuery
            ? (clone $customerQuery)
                ->selectRaw($this->dateExpression('created_at').' as day_key, COUNT(*) as aggregate')
                ->groupBy('day_key')
                ->orderBy('day_key')
                ->get()
                ->map(fn ($row) => [
                    'date' => $row->day_key,
                    'count' => (int) $row->aggregate,
                ])
                ->all()
            : [];

        return [
            'total' => $total,
            'new_this_month' => $newThisMonth,
            'converted' => $converted,
            'by_date' => $byDate,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function taskReport(Builder $taskQuery, User $user): array
    {
        $statusCounts = (clone $taskQuery)
            ->selectRaw('tasks.status, COUNT(*) as aggregate')
            ->groupBy('tasks.status')
            ->pluck('aggregate', 'status');

        $pending = (int) ($statusCounts['pending'] ?? 0);
        $completed = (int) ($statusCounts['completed'] ?? 0);
        $overdue = $this->overdueTasksQuery(clone $taskQuery)->count();

        $byEmployee = (clone $taskQuery)
            ->leftJoin('users', function ($join) use ($user) {
                $join->on('tasks.assigned_to', '=', 'users.id')
                    ->where('users.company_id', '=', $user->company_id);
            })
            ->selectRaw('tasks.assigned_to, COALESCE(users.name, ?) as employee_name, COUNT(*) as aggregate', ['Unassigned'])
            ->groupBy('tasks.assigned_to', 'users.name')
            ->orderByDesc('aggregate')
            ->get()
            ->map(fn ($row) => [
                'employee_id' => $row->assigned_to,
                'employee' => $row->employee_name,
                'count' => (int) $row->aggregate,
            ])
            ->all();

        $byDate = (clone $taskQuery)
            ->selectRaw($this->dateExpression('tasks.created_at').' as day_key, COUNT(*) as aggregate')
            ->groupBy('day_key')
            ->orderBy('day_key')
            ->get()
            ->map(fn ($row) => [
                'date' => $row->day_key,
                'count' => (int) $row->aggregate,
            ])
            ->all();

        return [
            'pending' => $pending,
            'completed' => $completed,
            'overdue' => $overdue,
            'in_progress' => (int) ($statusCounts['in_progress'] ?? 0),
            'cancelled' => (int) ($statusCounts['cancelled'] ?? 0),
            'total' => (int) $statusCounts->sum(),
            'by_employee' => $byEmployee,
            'by_date' => $byDate,
            'by_status_chart' => [
                'labels' => ['Pending', 'In Progress', 'Completed', 'Cancelled'],
                'data' => [
                    $pending,
                    (int) ($statusCounts['in_progress'] ?? 0),
                    $completed,
                    (int) ($statusCounts['cancelled'] ?? 0),
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function performanceReport(User $user, array $filters, Carbon $dateFrom, Carbon $dateTo): array
    {
        $base = Lead::visibleTo($user)
            ->whereBetween('leads.created_at', [$dateFrom, $dateTo]);

        $employeeId = $this->resolveEmployeeFilter($user, $filters['employee_id'] ?? null, 'leads');

        if ($employeeId !== null) {
            $base->where('leads.assigned_to', $employeeId);
        }

        if (filled($filters['source'] ?? null)) {
            $base->where('leads.source', $filters['source']);
        }

        // Performance ignores lead status filter so conversion stays meaningful.
        $rows = (clone $base)
            ->leftJoin('users', function ($join) use ($user) {
                $join->on('leads.assigned_to', '=', 'users.id')
                    ->where('users.company_id', '=', $user->company_id);
            })
            ->selectRaw('
                leads.assigned_to,
                COALESCE(users.name, ?) as employee_name,
                COUNT(*) as assigned_count,
                SUM(CASE WHEN leads.status = ? THEN 1 ELSE 0 END) as converted_count
            ', ['Unassigned', 'won'])
            ->groupBy('leads.assigned_to', 'users.name')
            ->orderByDesc('converted_count')
            ->orderByDesc('assigned_count')
            ->get();

        $employees = $rows->map(function ($row) {
            $assigned = (int) $row->assigned_count;
            $converted = (int) $row->converted_count;

            return [
                'employee_id' => $row->assigned_to,
                'employee' => $row->employee_name,
                'assigned' => $assigned,
                'converted' => $converted,
                'conversion_rate' => $assigned > 0
                    ? round(($converted / $assigned) * 100, 1)
                    : 0.0,
            ];
        });

        $totalAssigned = (int) $employees->sum('assigned');
        $totalConverted = (int) $employees->sum('converted');

        return [
            'leads_assigned' => $totalAssigned,
            'leads_converted' => $totalConverted,
            'conversion_rate' => $totalAssigned > 0
                ? round(($totalConverted / $totalAssigned) * 100, 1)
                : 0.0,
            'by_employee' => $employees->values()->all(),
            'top_performers' => $employees
                ->sortByDesc(fn (array $row) => [$row['converted'], $row['conversion_rate'], $row['assigned']])
                ->take(5)
                ->values()
                ->all(),
        ];
    }

    protected function overdueTasksQuery(Builder $query): Builder
    {
        return CrmAnalytics::applyOverdueTasks($query, 'tasks.status', 'tasks.due_date');
    }

    /**
     * Last 6 calendar months of visible leads.
     * Uses employee/source filters only — not the report date range or status —
     * so the growth chart stays meaningful when the hub is filtered to a short window.
     *
     * @param  array<string, mixed>  $filters
     * @return array{labels: list<string>, data: list<int>}
     */
    protected function monthlyLeadGrowth(User $user, array $filters): array
    {
        $query = Lead::visibleTo($user);

        $employeeId = $this->resolveEmployeeFilter($user, $filters['employee_id'] ?? null, 'leads');

        if ($employeeId !== null) {
            $query->where('leads.assigned_to', $employeeId);
        }

        if (filled($filters['source'] ?? null)) {
            $query->where('leads.source', $filters['source']);
        }

        return CrmAnalytics::monthlyLeadGrowth($query, 6, 'leads.created_at');
    }

    /**
     * @return array{labels: list<string>, data: list<int>}
     */
    protected function leadSourceDistribution(Builder $leadQuery): array
    {
        return CrmAnalytics::leadSourceDistribution($leadQuery, 'leads.source');
    }

    protected function dateExpression(string $column): string
    {
        return CrmAnalytics::dateExpression($column);
    }

    protected function monthExpression(string $column): string
    {
        return CrmAnalytics::monthExpression($column);
    }
}
