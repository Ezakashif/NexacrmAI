<x-app-layout>
    <x-slot name="header">
        <x-page-header
            title="Dashboard"
            :subtitle="'What needs your attention today, '.Auth::user()->name.'.'"
            :breadcrumbs="[
                ['label' => 'Home', 'url' => route('dashboard')],
                ['label' => 'Dashboard'],
            ]"
        >
            <x-slot:actions>
                <button type="button"
                        class="btn btn-sm btn-outline-secondary"
                        data-tour="tour-replay"
                        title="Replay the dashboard product tour">
                    <i class="fas fa-route" aria-hidden="true"></i>
                    Replay tour
                </button>
                @if (! empty($quickActions))
                    <span class="crm-header-actions__group" data-tour="quick-actions">
                        @foreach ($quickActions as $index => $action)
                            <a href="{{ $action['route'] }}"
                               class="btn btn-sm {{ $index === 0 ? 'btn-primary' : 'btn-outline-secondary' }}">
                                <i class="{{ $action['icon'] }}" aria-hidden="true"></i>
                                {{ $action['label'] }}
                            </a>
                        @endforeach
                    </span>
                @endif
            </x-slot:actions>
        </x-page-header>
    </x-slot>

    <div class="crm-dashboard">
        @php
            $announcement = app(\App\Services\SuperAdmin\PlatformSettingsService::class)->announcement();
            $canCreateLead = auth()->user()->can('create', App\Models\Lead::class);
            $canCreateTask = auth()->user()->can('create', App\Models\Task::class);
            $canCreateCustomer = auth()->user()->can('create', App\Models\Customer::class);
        @endphp

        @if ($announcement)
            <div class="crm-banner crm-banner--info crm-keep-alert" role="status">
                <div class="crm-banner__icon" aria-hidden="true"><i class="fas fa-bullhorn"></i></div>
                <div class="crm-banner__body">{{ $announcement }}</div>
            </div>
        @endif

        {{-- Attention strip --}}
        @if ($canViewLeads || $canViewTasks)
            <div class="crm-attention" aria-label="Key metrics">
                @if ($canViewLeads)
                    <x-dashboard.kpi-stat
                        label="Follow-ups Today"
                        :value="$todaysFollowUpsCount"
                        href="#todays-follow-ups"
                        meta="Review list"
                        tone="accent"
                    />
                @endif

                @if ($canViewTasks)
                    <x-dashboard.kpi-stat
                        label="Pending Tasks"
                        :value="$pendingTasksCount"
                        :href="route('tasks.index', ['status' => 'pending'])"
                        meta="View tasks"
                        tone="warning"
                    />

                    <x-dashboard.kpi-stat
                        label="Overdue Tasks"
                        :value="$overdueTasksCount"
                        href="#overdue-tasks"
                        meta="Review list"
                        tone="danger"
                    />
                @endif

                @if ($canViewLeads)
                    <x-dashboard.kpi-stat
                        label="Lead Conversion Rate"
                        :value="number_format($conversionRate, 1).'<sup>%</sup>'"
                        :href="route('leads.index')"
                        meta="View pipeline"
                        tone="success"
                    />
                @endif
            </div>
        @endif

        <div class="row">
            {{-- Work column --}}
            <div class="col-lg-8">
                @if ($canViewLeads)
                    <x-dashboard.section-card
                        title="Follow-ups today"
                        :badge="$todaysFollowUpsCount"
                        badge-tone="info"
                        id="todays-follow-ups"
                        :action-url="$todaysFollowUpsCount ? route('leads.index') : null"
                        action-label="View leads"
                    >
                        @if ($todaysFollowUps->isEmpty())
                            <x-dashboard.empty-state
                                class="crm-empty--compact"
                                icon="fas fa-calendar-check"
                                title="No follow-ups scheduled for today"
                                description="You're clear — schedule a follow-up on a lead when needed."
                                :action-url="$canCreateLead ? route('leads.create') : null"
                                :action-label="$canCreateLead ? 'Add lead' : null"
                            />
                        @else
                            <ul class="crm-feed">
                                @foreach ($todaysFollowUps as $lead)
                                    <li class="crm-feed__item">
                                        <div>
                                            <a href="{{ route('leads.show', $lead) }}" class="crm-feed__title">{{ $lead->name }}</a>
                                            <div class="crm-feed__meta">
                                                {{ $lead->statusLabel() }}
                                                @if ($lead->assignee)
                                                    · {{ $lead->assignee->name }}
                                                @endif
                                            </div>
                                        </div>
                                        <span class="crm-chip">{{ optional($lead->follow_up_date)->format('M j') }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </x-dashboard.section-card>
                @endif

                @if ($canViewTasks)
                    <x-dashboard.section-card
                        title="Overdue tasks"
                        :badge="$overdueTasksCount"
                        badge-tone="danger"
                        id="overdue-tasks"
                        :action-url="$overdueTasksCount ? route('tasks.index') : null"
                        action-label="View tasks"
                    >
                        @if ($overdueTasks->isEmpty())
                            <x-dashboard.empty-state
                                class="crm-empty--compact"
                                icon="fas fa-check-circle"
                                title="No overdue tasks"
                                description="Nice work — keep momentum by clearing pending work next."
                                :action-url="$canCreateTask ? route('tasks.create') : route('tasks.index', ['status' => 'pending'])"
                                :action-label="$canCreateTask ? 'Add task' : 'View pending'"
                            />
                        @else
                            <ul class="crm-feed">
                                @foreach ($overdueTasks as $task)
                                    <li class="crm-feed__item">
                                        <div>
                                            <a href="{{ route('tasks.show', $task) }}" class="crm-feed__title">{{ $task->title }}</a>
                                            <div class="crm-feed__meta">
                                                {{ ucfirst(str_replace('_', ' ', $task->status)) }}
                                                · {{ ucfirst($task->priority) }}
                                                @if ($task->assignee)
                                                    · {{ $task->assignee->name }}
                                                @endif
                                            </div>
                                        </div>
                                        <span class="crm-chip crm-chip--danger">
                                            {{ \Carbon\Carbon::parse($task->due_date)->format('M j') }}
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </x-dashboard.section-card>

                    <x-dashboard.section-card
                        title="Pending tasks"
                        :action-url="route('tasks.index', ['status' => 'pending'])"
                        action-label="View all"
                    >
                        @if ($pendingTasks->isEmpty())
                            <x-dashboard.empty-state
                                class="crm-empty--compact"
                                icon="fas fa-tasks"
                                title="No pending tasks"
                                description="Create a task when something needs follow-through."
                                :action-url="$canCreateTask ? route('tasks.create') : null"
                                :action-label="$canCreateTask ? 'Add task' : null"
                            />
                        @else
                            <div class="table-responsive crm-table-scroll">
                                <table class="table crm-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Priority</th>
                                            <th>Due</th>
                                            <th>Assignee</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($pendingTasks as $task)
                                            <tr>
                                                <td>
                                                    <a href="{{ route('tasks.show', $task) }}">{{ $task->title }}</a>
                                                </td>
                                                <td>
                                                    <span class="crm-priority crm-priority--{{ $task->priority }}">
                                                        <span class="crm-priority__dot" aria-hidden="true"></span>
                                                        {{ ucfirst($task->priority) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if ($task->due_date)
                                                        {{ \Carbon\Carbon::parse($task->due_date)->format('M j, Y') }}
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                                <td>{{ $task->assignee?->name ?? 'Unassigned' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </x-dashboard.section-card>
                @endif
            </div>

            {{-- Insights column --}}
            <div class="col-lg-4">
                @if ($canViewLeads)
                    <x-dashboard.section-card title="Lead pipeline" :padded="true" data-tour="lead-pipeline">
                        <div class="crm-insight-grid">
                            <a href="{{ route('leads.index', ['status' => 'new']) }}" class="crm-insight">
                                <div class="crm-insight__label">New leads</div>
                                <div class="crm-insight__value">{{ $newLeadsCount }}</div>
                            </a>
                            <a href="{{ route('leads.index', ['status' => 'won']) }}" class="crm-insight">
                                <div class="crm-insight__label">Won leads</div>
                                <div class="crm-insight__value">{{ $wonLeadsCount }}</div>
                            </a>
                            <a href="{{ route('leads.index', ['status' => 'lost']) }}" class="crm-insight">
                                <div class="crm-insight__label">Lost leads</div>
                                <div class="crm-insight__value">{{ $lostLeadsCount }}</div>
                            </a>
                        </div>
                    </x-dashboard.section-card>
                @endif

                @if ($canViewCustomers || $canViewLeads || $canViewTasks)
                    <x-dashboard.section-card title="Workspace totals" :padded="true" data-tour="workspace-totals">
                        <div class="crm-totals">
                            @if ($canViewCustomers)
                                <a href="{{ route('customers.index') }}" class="crm-total-row crm-total-row--link">
                                    <span class="crm-total-row__label">Customers</span>
                                    <span class="crm-total-row__value">{{ $customerCount }}</span>
                                </a>
                            @endif
                            @if ($canViewLeads)
                                <a href="{{ route('leads.index') }}" class="crm-total-row crm-total-row--link">
                                    <span class="crm-total-row__label">Total leads</span>
                                    <span class="crm-total-row__value">{{ $leadCount }}</span>
                                </a>
                            @endif
                            @if ($canViewTasks)
                                <a href="{{ route('tasks.index') }}" class="crm-total-row crm-total-row--link">
                                    <span class="crm-total-row__label">Total tasks</span>
                                    <span class="crm-total-row__value">{{ $taskCount }}</span>
                                </a>
                            @endif
                        </div>
                    </x-dashboard.section-card>
                @endif

                @if ($canViewLeads)
                    <x-dashboard.section-card title="Lead source distribution" :padded="true" data-tour="dashboard-charts">
                        <div id="lead-source-shell" class="crm-chart-shell crm-chart-shell--sm is-loading" aria-busy="true">
                            <div class="crm-chart-skeleton" aria-hidden="true"></div>
                            <canvas id="leadSourceChart" aria-label="Lead source distribution chart"></canvas>
                        </div>
                    </x-dashboard.section-card>
                @endif
            </div>
        </div>

        @if ($canViewLeads)
            <x-dashboard.section-card title="Monthly lead growth" :padded="true">
                <div id="monthly-lead-growth-shell" class="crm-chart-shell is-loading" aria-busy="true">
                    <div class="crm-chart-skeleton" aria-hidden="true"></div>
                    <canvas id="monthlyLeadGrowthChart" aria-label="Monthly lead growth chart"></canvas>
                </div>
            </x-dashboard.section-card>
        @endif

        {{-- Recent feeds --}}
        <div class="row crm-dashboard__recent">
            @if ($canViewLeads)
                <div class="col-lg-4">
                    <x-dashboard.section-card
                        title="Recent leads"
                        :action-url="route('leads.index')"
                        action-label="View all"
                    >
                        @if ($recentLeads->isEmpty())
                            <x-dashboard.empty-state
                                class="crm-empty--compact"
                                icon="fas fa-user-plus"
                                title="No leads yet"
                                description="Add your first lead to start the pipeline."
                                :action-url="$canCreateLead ? route('leads.create') : null"
                                :action-label="$canCreateLead ? 'Add lead' : null"
                            />
                        @else
                            <ul class="crm-feed">
                                @foreach ($recentLeads as $lead)
                                    <li class="crm-feed__item">
                                        <div>
                                            <a href="{{ route('leads.show', $lead) }}" class="crm-feed__title">{{ $lead->name }}</a>
                                            <div class="crm-feed__meta">
                                                {{ $lead->statusLabel() }}
                                                · {{ $lead->created_at->diffForHumans() }}
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </x-dashboard.section-card>
                </div>
            @endif

            @if ($canViewCustomers)
                <div class="col-lg-4">
                    <x-dashboard.section-card
                        title="Recent customers"
                        :action-url="route('customers.index')"
                        action-label="View all"
                    >
                        @if ($recentCustomers->isEmpty())
                            <x-dashboard.empty-state
                                class="crm-empty--compact"
                                icon="fas fa-building"
                                title="No customers yet"
                                description="Convert a won lead or add a customer."
                                :action-url="$canCreateCustomer ? route('customers.create') : null"
                                :action-label="$canCreateCustomer ? 'Add customer' : null"
                            />
                        @else
                            <ul class="crm-feed">
                                @foreach ($recentCustomers as $customer)
                                    <li class="crm-feed__item">
                                        <div>
                                            <a href="{{ route('customers.show', $customer) }}" class="crm-feed__title">{{ $customer->name }}</a>
                                            <div class="crm-feed__meta">
                                                {{ $customer->company_name ?? 'No company' }}
                                                · {{ $customer->created_at->diffForHumans() }}
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </x-dashboard.section-card>
                </div>
            @endif

            @if ($canViewActivityLogs)
                <div class="col-lg-4">
                    <x-dashboard.section-card
                        title="Recent activities"
                        :action-url="route('activity-logs.index')"
                        action-label="View all"
                    >
                        @if ($recentActivities->isEmpty())
                            <x-dashboard.empty-state
                                class="crm-empty--compact"
                                icon="fas fa-stream"
                                title="No recent activity"
                                description="CRM actions will show up here as your team works."
                            />
                        @else
                            <ul class="crm-feed">
                                @foreach ($recentActivities as $activity)
                                    @php $subjectUrl = $activity->subjectShowUrl(); @endphp
                                    <li class="crm-feed__item">
                                        <div>
                                            <div class="crm-feed__title">{{ $activity->actionLabel() }}</div>
                                            <div class="crm-feed__meta">
                                                @if ($subjectUrl)
                                                    <a href="{{ $subjectUrl }}">{{ $activity->description() }}</a>
                                                @else
                                                    {{ $activity->description() }}
                                                @endif
                                            </div>
                                            <div class="crm-feed__meta">
                                                {{ $activity->actor?->name ?? 'System' }}
                                                · {{ $activity->created_at->diffForHumans() }}
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </x-dashboard.section-card>
                </div>
            @endif
        </div>
    </div>

    @if ($canViewLeads)
        @push('js')
            @include('partials.dashboard.chart-scripts')
        @endpush
    @endif

    @push('css')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/driver.js@1.3.5/dist/driver.css">
        <link rel="stylesheet" href="{{ asset('css/dashboard-tour.css') }}">
    @endpush

    @push('js')
        @php
            $dashboardTourBoot = [
                'autoStart' => (bool) ($shouldStartDashboardTour ?? false),
                'steps' => $dashboardTourSteps ?? [],
                'completeUrl' => route('dashboard.tour.complete'),
                'restartUrl' => route('dashboard.tour.restart'),
                'csrf' => csrf_token(),
            ];
        @endphp
        <script>
            window.DashboardTourBoot = @json($dashboardTourBoot);
        </script>
        <script src="https://cdn.jsdelivr.net/npm/driver.js@1.3.5/dist/driver.js.iife.js"></script>
        <script src="{{ asset('js/dashboard-tour.js') }}"></script>
    @endpush
</x-app-layout>
