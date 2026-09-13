<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Task;
use App\Models\User;
use App\Services\Analytics\CrmAnalytics;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    /**
     * Build the dashboard payload for the authenticated user.
     *
     * Lead and task analytics are scoped to the current assignee unless the
     * user has view_all.leads / view_all.tasks.
     *
     * @return array<string, mixed>
     */
    public function forUser(User $user): array
    {
        if (app()->environment('testing')) {
            return $this->buildForUser($user);
        }

        $cacheKey = sprintf(
            'dashboard:%s:%s:%s',
            $user->company_id ?? 'none',
            $user->id,
            md5($user->permissionSlugs()->implode(',')),
        );

        return Cache::remember($cacheKey, now()->addSeconds(60), function () use ($user) {
            return $this->buildForUser($user);
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function buildForUser(User $user): array
    {
        $canViewLeads = $user->hasPermission('view.leads');
        $canViewTasks = $user->hasPermission('view.tasks');
        $canViewCustomers = $user->hasPermission('view.customers');
        $canViewAllActivityLogs = $user->hasPermission('view.activity_logs');
        $canViewOwnActivityLogs = $user->hasPermission('view_own.activity_logs');
        $canViewActivityLogs = $canViewAllActivityLogs || $canViewOwnActivityLogs;
        $canViewAllLeadAnalytics = $user->canViewAllLeads();

        $taskQuery = $canViewTasks ? Task::visibleTo($user) : null;
        $leadQuery = $canViewLeads ? Lead::visibleTo($user) : null;

        $leadStatusCounts = $canViewLeads
            ? (clone $leadQuery)
                ->selectRaw('status, COUNT(*) as aggregate')
                ->groupBy('status')
                ->pluck('aggregate', 'status')
            : collect();

        $taskStatusCounts = $canViewTasks
            ? (clone $taskQuery)
                ->selectRaw('status, COUNT(*) as aggregate')
                ->groupBy('status')
                ->pluck('aggregate', 'status')
            : collect();

        $newLeads = (int) ($leadStatusCounts['new'] ?? 0);
        $wonLeads = (int) ($leadStatusCounts['won'] ?? 0);
        $lostLeads = (int) ($leadStatusCounts['lost'] ?? 0);
        $closedLeads = $wonLeads + $lostLeads;

        return [
            'canViewLeads' => $canViewLeads,
            'canViewTasks' => $canViewTasks,
            'canViewCustomers' => $canViewCustomers,
            'canViewActivityLogs' => $canViewActivityLogs,
            'canViewAllLeadAnalytics' => $canViewAllLeadAnalytics,

            'customerCount' => $canViewCustomers ? Customer::count() : null,
            'leadCount' => $canViewLeads ? (int) $leadStatusCounts->sum() : null,
            'taskCount' => $canViewTasks ? (int) $taskStatusCounts->sum() : null,

            'todaysFollowUpsCount' => $canViewLeads
                ? $this->todaysFollowUpsQuery(clone $leadQuery)->count()
                : null,
            'pendingTasksCount' => $canViewTasks
                ? (int) ($taskStatusCounts['pending'] ?? 0)
                : null,
            'overdueTasksCount' => $canViewTasks
                ? $this->overdueTasksQuery(clone $taskQuery)->count()
                : null,

            'newLeadsCount' => $canViewLeads ? $newLeads : null,
            'wonLeadsCount' => $canViewLeads ? $wonLeads : null,
            'lostLeadsCount' => $canViewLeads ? $lostLeads : null,
            'conversionRate' => $canViewLeads
                ? ($closedLeads > 0 ? round(($wonLeads / $closedLeads) * 100, 1) : 0.0)
                : null,

            'todaysFollowUps' => $canViewLeads
                ? $this->todaysFollowUpsQuery(clone $leadQuery)
                    ->with('assignee')
                    ->orderBy('name')
                    ->limit(8)
                    ->get()
                : collect(),

            'pendingTasks' => $canViewTasks
                ? (clone $taskQuery)
                    ->with(['assignee', 'lead', 'customer'])
                    ->where('status', 'pending')
                    ->orderByRaw('due_date is null')
                    ->orderBy('due_date')
                    ->limit(8)
                    ->get()
                : collect(),

            'overdueTasks' => $canViewTasks
                ? $this->overdueTasksQuery(clone $taskQuery)
                    ->with(['assignee', 'lead', 'customer'])
                    ->orderBy('due_date')
                    ->limit(8)
                    ->get()
                : collect(),

            'recentLeads' => $canViewLeads
                ? (clone $leadQuery)->with('assignee')->latest()->limit(8)->get()
                : collect(),

            'recentCustomers' => $canViewCustomers
                ? Customer::latest()->limit(8)->get()
                : collect(),

            'recentActivities' => $canViewActivityLogs
                ? $this->recentActivitiesQuery($user, $canViewAllActivityLogs)
                    ->with(['actor', 'subject'])
                    ->limit(8)
                    ->get()
                : collect(),

            'monthlyLeadGrowth' => $canViewLeads
                ? $this->monthlyLeadGrowth(clone $leadQuery, 6)
                : [
                    'labels' => [],
                    'data' => [],
                ],

            'leadSourceDistribution' => $canViewLeads
                ? $this->leadSourceDistribution(clone $leadQuery)
                : [
                    'labels' => [],
                    'data' => [],
                ],

            'quickActions' => $this->quickActions($user),
        ];
    }

    protected function todaysFollowUpsQuery(Builder $leadQuery): Builder
    {
        return $leadQuery
            ->whereDate('follow_up_date', today())
            ->whereNotIn('status', ['won', 'lost']);
    }

    protected function overdueTasksQuery(Builder $query): Builder
    {
        return CrmAnalytics::applyOverdueTasks($query);
    }

    protected function recentActivitiesQuery(User $user, bool $canViewAll): Builder
    {
        $query = ActivityLog::query()->forTenant()->latest();

        if (! $canViewAll) {
            $query->where('user_id', $user->id);
        }

        return $query;
    }

    /**
     * @return array{labels: list<string>, data: list<int>}
     */
    protected function monthlyLeadGrowth(Builder $leadQuery, int $months): array
    {
        return CrmAnalytics::monthlyLeadGrowth($leadQuery, $months);
    }

    /**
     * @return array{labels: list<string>, data: list<int>}
     */
    protected function leadSourceDistribution(Builder $leadQuery): array
    {
        return CrmAnalytics::leadSourceDistribution($leadQuery);
    }

    /**
     * @return list<array{label: string, route: string, icon: string, class: string}>
     */
    protected function quickActions(User $user): array
    {
        $actions = [];

        if ($user->hasPermission('create.leads')) {
            $actions[] = [
                'label' => 'Add Lead',
                'route' => route('leads.create'),
                'icon' => 'fas fa-user-plus',
                'class' => 'btn-primary',
            ];
        }

        if ($user->hasPermission('create.tasks')) {
            $actions[] = [
                'label' => 'Add Task',
                'route' => route('tasks.create'),
                'icon' => 'fas fa-plus-circle',
                'class' => 'btn-warning',
            ];
        }

        if ($user->hasPermission('create.customers')) {
            $actions[] = [
                'label' => 'Add Customer',
                'route' => route('customers.create'),
                'icon' => 'fas fa-address-book',
                'class' => 'btn-info',
            ];
        }

        if ($user->hasPermission('view.leads')) {
            $actions[] = [
                'label' => 'Lead Board',
                'route' => route('leads.index'),
                'icon' => 'fas fa-columns',
                'class' => 'btn-outline-secondary',
            ];
        }

        if ($user->hasPermission('view.tasks')) {
            $actions[] = [
                'label' => 'Task Board',
                'route' => route('tasks.index'),
                'icon' => 'fas fa-tasks',
                'class' => 'btn-outline-secondary',
            ];
        }

        return $actions;
    }
}
