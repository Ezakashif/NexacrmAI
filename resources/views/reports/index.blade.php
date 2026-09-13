<x-app-layout>
    <x-slot name="header">
        <x-page-header
            title="Reports"
            :subtitle="\Carbon\Carbon::parse($filters['date_from'])->format('M j, Y').' – '.\Carbon\Carbon::parse($filters['date_to'])->format('M j, Y')"
            :breadcrumbs="[
                ['label' => 'Home', 'url' => route('dashboard')],
                ['label' => 'Reports'],
            ]"
        />
    </x-slot>

    <div class="crm-dashboard crm-reports">
        <x-list-filters :reset-url="route('reports.index')">
            <div class="col-md-2 mb-2">
                <label for="date_from" class="small text-muted mb-1">From</label>
                <input id="date_from" name="date_from" type="date" class="form-control form-control-sm"
                       value="{{ $filters['date_from'] }}">
            </div>
            <div class="col-md-2 mb-2">
                <label for="date_to" class="small text-muted mb-1">To</label>
                <input id="date_to" name="date_to" type="date" class="form-control form-control-sm"
                       value="{{ $filters['date_to'] }}">
            </div>
            @if ($canFilterEmployees)
                <div class="col-md-2 mb-2">
                    <label for="employee_id" class="small text-muted mb-1">Employee</label>
                    <select id="employee_id" name="employee_id" class="form-control form-control-sm">
                        <option value="">All employees</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}" @selected(($filters['employee_id'] ?? null) == $employee->id)>
                                {{ $employee->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif
            @if ($canViewLeads)
                <div class="col-md-2 mb-2">
                    <label for="source" class="small text-muted mb-1">Lead source</label>
                    <select id="source" name="source" class="form-control form-control-sm">
                        <option value="">All sources</option>
                        @foreach ($leadSources as $source)
                            <option value="{{ $source }}" @selected(($filters['source'] ?? '') === $source)>
                                {{ ucfirst(str_replace('_', ' ', $source)) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label for="status" class="small text-muted mb-1">Lead status</label>
                    <select id="status" name="status" class="form-control form-control-sm">
                        <option value="">All statuses</option>
                        @foreach ($leadStatuses as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
        </x-list-filters>

        @if ($canViewLeads || $canViewCustomers || $canViewTasks)
            <div class="crm-attention crm-attention--reports" aria-label="Report summary">
                @if ($canViewLeads && $leads)
                    <x-dashboard.kpi-stat
                        label="Total Leads"
                        :value="$leads['total']"
                        :href="route('leads.index')"
                        meta="View pipeline"
                        tone="accent"
                    />
                @endif

                @if ($canViewLeads && $performance)
                    <x-dashboard.kpi-stat
                        label="Conversion Rate"
                        :value="number_format($performance['conversion_rate'], 1).'<sup>%</sup>'"
                        meta="Won ÷ assigned"
                        tone="success"
                    />
                @endif

                @if ($canViewCustomers && $customers)
                    <x-dashboard.kpi-stat
                        label="Customers"
                        :value="$customers['total']"
                        :href="route('customers.index')"
                        meta="In range"
                        tone="accent"
                    />
                @endif

                @if ($canViewTasks && $tasks)
                    <x-dashboard.kpi-stat
                        label="Overdue Tasks"
                        :value="$tasks['overdue']"
                        :href="route('tasks.index')"
                        meta="Needs attention"
                        tone="danger"
                    />
                @endif
            </div>
        @endif

        @if ($canViewLeads && $leads)
            <div class="crm-section-heading">
                <div>
                    <h2 class="crm-section-heading__title">Leads</h2>
                    <p class="crm-section-heading__meta">Pipeline volume, sources, and growth for the selected range.</p>
                </div>
                @if ($canExport)
                    <a href="{{ route('reports.export', ['type' => 'leads'] + $filters) }}" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-file-csv" aria-hidden="true"></i> Export CSV
                    </a>
                @endif
            </div>

            <div class="crm-insight-grid crm-insight-grid--4 mb-3">
                <div class="crm-insight">
                    <div class="crm-insight__label">Total</div>
                    <div class="crm-insight__value">{{ $leads['total'] }}</div>
                </div>
                @foreach (array_slice($leads['by_status'], 0, 3) as $statusRow)
                    <div class="crm-insight">
                        <div class="crm-insight__label">{{ $statusRow['label'] }}</div>
                        <div class="crm-insight__value">{{ $statusRow['count'] }}</div>
                    </div>
                @endforeach
            </div>

            <div class="row">
                <div class="col-lg-6">
                    <x-dashboard.section-card title="Leads by status" :padded="true">
                        <div id="leads-by-status-shell" class="crm-chart-shell crm-chart-shell--sm is-loading" aria-busy="true">
                            <div class="crm-chart-skeleton" aria-hidden="true"></div>
                            <canvas id="leadsByStatusChart" aria-label="Leads by status chart"></canvas>
                        </div>
                    </x-dashboard.section-card>
                </div>
                <div class="col-lg-6">
                    <x-dashboard.section-card title="Leads by source" :padded="true">
                        <div id="leads-by-source-shell" class="crm-chart-shell crm-chart-shell--sm is-loading" aria-busy="true">
                            <div class="crm-chart-skeleton" aria-hidden="true"></div>
                            <canvas id="leadsBySourceChart" aria-label="Leads by source chart"></canvas>
                        </div>
                    </x-dashboard.section-card>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-7">
                    <x-dashboard.section-card title="Monthly lead growth" :padded="true">
                        <div id="monthly-lead-growth-shell" class="crm-chart-shell is-loading" aria-busy="true">
                            <div class="crm-chart-skeleton" aria-hidden="true"></div>
                            <canvas id="monthlyLeadGrowthChart" aria-label="Monthly lead growth chart"></canvas>
                        </div>
                    </x-dashboard.section-card>
                </div>
                <div class="col-lg-5">
                    <x-dashboard.section-card title="Leads by assigned employee">
                        @if (empty($leads['by_assignee']))
                            <x-dashboard.empty-state
                                class="crm-empty--compact"
                                icon="fas fa-user-friends"
                                title="No leads in this range"
                                description="Try widening the date range or clearing filters."
                            />
                        @else
                            <div class="table-responsive crm-table-scroll">
                                <table class="table crm-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>Employee</th>
                                            <th>Leads</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($leads['by_assignee'] as $row)
                                            <tr>
                                                <td>{{ $row['employee'] }}</td>
                                                <td class="crm-table__num">{{ $row['count'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </x-dashboard.section-card>
                </div>
            </div>

            <x-dashboard.section-card title="Leads by date">
                @if (empty($leads['by_date']))
                    <x-dashboard.empty-state
                        class="crm-empty--compact"
                        icon="fas fa-calendar"
                        title="No daily lead data"
                        description="Lead activity will appear here once records exist."
                    />
                @else
                    <div class="table-responsive crm-table-scroll">
                        <table class="table crm-table mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Leads</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($leads['by_date'] as $day => $count)
                                    <tr>
                                        <td>{{ $day }}</td>
                                        <td class="crm-table__num">{{ $count }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-dashboard.section-card>
        @endif

        @if ($canViewCustomers && $customers)
            <div class="crm-section-heading">
                <div>
                    <h2 class="crm-section-heading__title">Customers</h2>
                    <p class="crm-section-heading__meta">New accounts and conversions in the selected window.</p>
                </div>
                @if ($canExport)
                    <a href="{{ route('reports.export', ['type' => 'customers'] + $filters) }}" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-file-csv" aria-hidden="true"></i> Export CSV
                    </a>
                @endif
            </div>

            <div class="crm-insight-grid crm-insight-grid--3 mb-3">
                <div class="crm-insight">
                    <div class="crm-insight__label">Total (range)</div>
                    <div class="crm-insight__value">{{ $customers['total'] }}</div>
                </div>
                <div class="crm-insight">
                    <div class="crm-insight__label">New this month</div>
                    <div class="crm-insight__value">{{ $customers['new_this_month'] }}</div>
                </div>
                <div class="crm-insight">
                    <div class="crm-insight__label">Converted (won)</div>
                    <div class="crm-insight__value">{{ $customers['converted'] }}</div>
                </div>
            </div>

            <x-dashboard.section-card title="Customers by date">
                @if (empty($customers['by_date']))
                    <x-dashboard.empty-state
                        class="crm-empty--compact"
                        icon="fas fa-building"
                        title="No customers in this range"
                        description="Converted or created customers will show up here."
                    />
                @else
                    <div class="table-responsive crm-table-scroll">
                        <table class="table crm-table mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Customers</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($customers['by_date'] as $row)
                                    <tr>
                                        <td>{{ $row['date'] }}</td>
                                        <td class="crm-table__num">{{ $row['count'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-dashboard.section-card>
        @endif

        @if ($canViewTasks && $tasks)
            <div class="crm-section-heading">
                <div>
                    <h2 class="crm-section-heading__title">Tasks</h2>
                    <p class="crm-section-heading__meta">Workload, completion, and overdue pressure.</p>
                </div>
                @if ($canExport)
                    <a href="{{ route('reports.export', ['type' => 'tasks'] + $filters) }}" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-file-csv" aria-hidden="true"></i> Export CSV
                    </a>
                @endif
            </div>

            <div class="crm-attention crm-attention--3 mb-3">
                <x-dashboard.kpi-stat
                    label="Pending Tasks"
                    :value="$tasks['pending']"
                    :href="route('tasks.index', ['status' => 'pending'])"
                    meta="View tasks"
                    tone="warning"
                />
                <x-dashboard.kpi-stat
                    label="Completed Tasks"
                    :value="$tasks['completed']"
                    :href="route('tasks.index', ['status' => 'completed'])"
                    meta="View tasks"
                    tone="success"
                />
                <x-dashboard.kpi-stat
                    label="Overdue Tasks"
                    :value="$tasks['overdue']"
                    :href="route('tasks.index')"
                    meta="Needs attention"
                    tone="danger"
                />
            </div>

            <div class="row">
                <div class="col-lg-5">
                    <x-dashboard.section-card title="Tasks by status" :padded="true">
                        <div id="tasks-by-status-shell" class="crm-chart-shell crm-chart-shell--sm is-loading" aria-busy="true">
                            <div class="crm-chart-skeleton" aria-hidden="true"></div>
                            <canvas id="tasksByStatusChart" aria-label="Tasks by status chart"></canvas>
                        </div>
                    </x-dashboard.section-card>
                </div>
                <div class="col-lg-7">
                    <x-dashboard.section-card title="Tasks by employee">
                        @if (empty($tasks['by_employee']))
                            <x-dashboard.empty-state
                                class="crm-empty--compact"
                                icon="fas fa-tasks"
                                title="No tasks in this range"
                                description="Assigned work will appear once tasks are created."
                            />
                        @else
                            <div class="table-responsive crm-table-scroll">
                                <table class="table crm-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>Employee</th>
                                            <th>Tasks</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($tasks['by_employee'] as $row)
                                            <tr>
                                                <td>{{ $row['employee'] }}</td>
                                                <td class="crm-table__num">{{ $row['count'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </x-dashboard.section-card>
                </div>
            </div>

            <x-dashboard.section-card title="Tasks by date">
                @if (empty($tasks['by_date']))
                    <x-dashboard.empty-state
                        class="crm-empty--compact"
                        icon="fas fa-calendar"
                        title="No daily task data"
                        description="Task activity will appear here for the selected dates."
                    />
                @else
                    <div class="table-responsive crm-table-scroll">
                        <table class="table crm-table mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Tasks</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($tasks['by_date'] as $row)
                                    <tr>
                                        <td>{{ $row['date'] }}</td>
                                        <td class="crm-table__num">{{ $row['count'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-dashboard.section-card>
        @endif

        @if ($canViewLeads && $performance)
            <div class="crm-section-heading">
                <div>
                    <h2 class="crm-section-heading__title">Sales Performance</h2>
                    <p class="crm-section-heading__meta">Assignment volume and conversion outcomes.</p>
                </div>
                @if ($canExport)
                    <a href="{{ route('reports.export', ['type' => 'performance'] + $filters) }}" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-file-csv" aria-hidden="true"></i> Export CSV
                    </a>
                @endif
            </div>

            <div class="crm-attention crm-attention--3 mb-3">
                <x-dashboard.kpi-stat
                    label="Leads Assigned"
                    :value="$performance['leads_assigned']"
                    meta="In range"
                    tone="accent"
                />
                <x-dashboard.kpi-stat
                    label="Leads Converted"
                    :value="$performance['leads_converted']"
                    meta="Won outcomes"
                    tone="success"
                />
                <x-dashboard.kpi-stat
                    label="Conversion Rate"
                    :value="number_format($performance['conversion_rate'], 1).'<sup>%</sup>'"
                    meta="Won ÷ assigned"
                    tone="warning"
                />
            </div>

            <x-dashboard.section-card title="Top performing employees">
                @if (empty($performance['top_performers']))
                    <x-dashboard.empty-state
                        class="crm-empty--compact"
                        icon="fas fa-chart-line"
                        title="No performance data in this range"
                        description="Assign and convert leads to populate this leaderboard."
                    />
                @else
                    <div class="table-responsive">
                        <table class="table crm-table mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Employee</th>
                                    <th>Assigned</th>
                                    <th>Converted</th>
                                    <th>Conversion rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($performance['top_performers'] as $index => $row)
                                    <tr>
                                        <td class="crm-table__num">{{ $index + 1 }}</td>
                                        <td>
                                            <span class="crm-feed__title">{{ $row['employee'] }}</span>
                                        </td>
                                        <td class="crm-table__num">{{ $row['assigned'] }}</td>
                                        <td class="crm-table__num">{{ $row['converted'] }}</td>
                                        <td>
                                            <span class="crm-chip {{ $row['conversion_rate'] >= 30 ? 'crm-chip--success' : '' }}">
                                                {{ number_format($row['conversion_rate'], 1) }}%
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-dashboard.section-card>
        @endif

        @if (! $canViewLeads && ! $canViewCustomers && ! $canViewTasks)
            <x-dashboard.section-card title="Reports" :padded="true">
                <x-dashboard.empty-state
                    class="crm-empty--compact"
                    icon="fas fa-chart-bar"
                    title="No report access"
                    description="You do not have permission to view lead, customer, or task reports."
                />
            </x-dashboard.section-card>
        @endif
    </div>

    @push('js')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.0/Chart.bundle.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var styles = getComputedStyle(document.documentElement);
                var accent = (styles.getPropertyValue('--crm-chart-1') || '#2563eb').trim();
                var palette = [
                    (styles.getPropertyValue('--crm-chart-1') || '#2563eb').trim(),
                    (styles.getPropertyValue('--crm-chart-2') || '#059669').trim(),
                    (styles.getPropertyValue('--crm-chart-3') || '#d97706').trim(),
                    (styles.getPropertyValue('--crm-chart-4') || '#7c3aed').trim(),
                    (styles.getPropertyValue('--crm-chart-5') || '#0891b2').trim(),
                    (styles.getPropertyValue('--crm-chart-6') || '#ea580c').trim(),
                    (styles.getPropertyValue('--crm-chart-7') || '#64748b').trim()
                ];

                function hasChartData(data) {
                    return (data || []).some(function (value) {
                        return Number(value) > 0;
                    });
                }

                function markReady(shellId) {
                    var shell = document.getElementById(shellId);
                    if (shell) {
                        shell.classList.remove('is-loading');
                        shell.classList.add('is-ready');
                        shell.setAttribute('aria-busy', 'false');
                    }
                }

                function showEmptyChart(shellId, canvas) {
                    var shell = document.getElementById(shellId);
                    if (shell) {
                        shell.classList.remove('is-loading');
                        shell.classList.add('is-ready', 'is-empty');
                        shell.setAttribute('aria-busy', 'false');
                        shell.innerHTML =
                            '<div class="crm-empty crm-empty--compact">' +
                                '<div class="crm-empty__icon" aria-hidden="true"><i class="fas fa-chart-pie"></i></div>' +
                                '<p class="crm-empty__title">No data for the selected filters</p>' +
                                '<p class="crm-empty__desc">Try a wider date range or clear filters.</p>' +
                            '</div>';
                        return;
                    }
                    if (canvas && canvas.parentNode) {
                        canvas.parentNode.innerHTML = '<p class="text-muted text-center mb-0 py-5">No data for the selected filters.</p>';
                    }
                }

                function makeDoughnut(id, shellId, labels, data) {
                    var canvas = document.getElementById(id);
                    if (! canvas || typeof Chart === 'undefined') return;

                    if (! hasChartData(data)) {
                        showEmptyChart(shellId, canvas);
                        return;
                    }

                    new Chart(canvas.getContext('2d'), {
                        type: 'doughnut',
                        data: {
                            labels: labels,
                            datasets: [{
                                data: data,
                                backgroundColor: palette,
                                borderWidth: 2,
                                borderColor: '#ffffff'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutoutPercentage: 68,
                            legend: {
                                position: window.innerWidth < 992 ? 'bottom' : 'right',
                                labels: {
                                    boxWidth: 10,
                                    fontColor: '#64748b',
                                    fontSize: 11,
                                    padding: 12
                                }
                            },
                            tooltips: {
                                backgroundColor: '#0f172a',
                                cornerRadius: 6,
                                xPadding: 10,
                                yPadding: 8
                            }
                        }
                    });
                    markReady(shellId);
                }

                function makeLine(id, shellId, labels, data) {
                    var canvas = document.getElementById(id);
                    if (! canvas || typeof Chart === 'undefined') return;

                    if (! hasChartData(data)) {
                        showEmptyChart(shellId, canvas);
                        return;
                    }

                    new Chart(canvas.getContext('2d'), {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Leads',
                                data: data,
                                borderColor: accent,
                                backgroundColor: 'rgba(37, 99, 235, 0.08)',
                                borderWidth: 2,
                                pointRadius: 2.5,
                                pointHoverRadius: 4,
                                pointBackgroundColor: accent,
                                lineTension: 0.3,
                                fill: true
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            legend: { display: false },
                            tooltips: {
                                backgroundColor: '#0f172a',
                                cornerRadius: 6,
                                xPadding: 10,
                                yPadding: 8
                            },
                            scales: {
                                xAxes: [{
                                    gridLines: { display: false, drawBorder: false },
                                    ticks: { fontColor: '#94a3b8', fontSize: 11 }
                                }],
                                yAxes: [{
                                    gridLines: { color: '#e2e8f0', zeroLineColor: '#e2e8f0', drawBorder: false },
                                    ticks: {
                                        beginAtZero: true,
                                        precision: 0,
                                        fontColor: '#94a3b8',
                                        fontSize: 11,
                                        padding: 8
                                    }
                                }]
                            }
                        }
                    });
                    markReady(shellId);
                }

                @if($canViewLeads && $leads)
                    makeDoughnut(
                        'leadsByStatusChart',
                        'leads-by-status-shell',
                        @json($leads['by_status_chart']['labels']),
                        @json($leads['by_status_chart']['data'])
                    );

                    makeDoughnut(
                        'leadsBySourceChart',
                        'leads-by-source-shell',
                        @json($leads['by_source_chart']['labels']),
                        @json($leads['by_source_chart']['data'])
                    );

                    makeLine(
                        'monthlyLeadGrowthChart',
                        'monthly-lead-growth-shell',
                        @json($leads['monthly_growth']['labels']),
                        @json($leads['monthly_growth']['data'])
                    );
                @endif

                @if($canViewTasks && $tasks)
                    makeDoughnut(
                        'tasksByStatusChart',
                        'tasks-by-status-shell',
                        @json($tasks['by_status_chart']['labels']),
                        @json($tasks['by_status_chart']['data'])
                    );
                @endif
            });
        </script>
    @endpush
</x-app-layout>
