@extends('superadmin.layout')

@section('title', 'Dashboard')
@section('heading', 'Platform overview')
@section('subheading', 'Health, growth, and tenant activity across the CRM')

@section('content')
<div class="sa-card mb-3">
    <div class="d-flex flex-wrap align-items-center" style="gap: 0.5rem;">
        <span class="sa-muted mr-2">Quick actions</span>
        <a href="{{ route('superadmin.companies.create') }}" class="btn btn-sm btn-info">Create Company</a>
        <a href="{{ route('superadmin.super-admins.create') }}" class="btn btn-sm btn-outline-light">Create Super Admin</a>
        <a href="{{ route('superadmin.settings.edit') }}#announcement" class="btn btn-sm btn-outline-light">Broadcast Announcement</a>
        <a href="{{ route('superadmin.companies.export') }}" class="btn btn-sm btn-outline-light">Export Companies</a>
        <a href="{{ route('superadmin.settings.edit') }}" class="btn btn-sm btn-outline-light">System Settings</a>
        <a href="{{ route('superadmin.contact-inquiries.index') }}" class="btn btn-sm btn-outline-light">
            Contact inquiries
            @if (($newContactInquiryCount ?? 0) > 0)
                <span class="badge badge-warning ml-1">{{ $newContactInquiryCount }}</span>
            @endif
        </a>
    </div>
</div>

<div class="row">
    @foreach ([
        'companies_total' => 'Total Companies',
        'companies_active' => 'Active Companies',
        'companies_suspended' => 'Suspended Companies',
        'companies_trial' => 'Trial Companies',
        'companies_expired' => 'Expired Companies',
        'tenant_users' => 'Tenant Users',
        'super_admin_users' => 'Super Admins',
        'companies_new_this_month' => 'New Companies This Month',
    ] as $key => $label)
        <div class="col-xl-3 col-md-4 col-sm-6">
            <div class="sa-card">
                <div class="sa-muted mb-1">{{ $label }}</div>
                <div class="sa-stat">{{ number_format($stats[$key]) }}</div>
            </div>
        </div>
    @endforeach
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="sa-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 mb-0 text-white">Platform growth</h2>
                <span class="sa-muted small">Last 12 months</span>
            </div>
            <canvas id="sa-companies-chart" height="120"></canvas>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="sa-card">
                    <h2 class="h6 text-white mb-3">Leads created</h2>
                    <canvas id="sa-leads-chart" height="160"></canvas>
                </div>
            </div>
            <div class="col-md-6">
                <div class="sa-card">
                    <h2 class="h6 text-white mb-3">Customers created</h2>
                    <canvas id="sa-customers-chart" height="160"></canvas>
                </div>
            </div>
        </div>

        <div class="sa-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 mb-0 text-white">Recent companies</h2>
                <a href="{{ route('superadmin.companies.index') }}" class="btn btn-sm btn-outline-light">View all</a>
            </div>
            @if ($recentCompanies->isEmpty())
                <div class="sa-empty py-4">
                    <div class="sa-empty__icon" aria-hidden="true"><i class="fas fa-building"></i></div>
                    <h3 class="sa-empty__title">No companies yet</h3>
                    <p class="sa-empty__text">Provision a tenant to see them here.</p>
                    <a href="{{ route('superadmin.companies.create') }}" class="btn btn-sm btn-info">Create Company</a>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                        <tr>
                            <th>Company</th>
                            <th>Owner</th>
                            <th>Plan</th>
                            <th>Users</th>
                            <th>Status</th>
                            <th>Created</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($recentCompanies as $company)
                            <tr>
                                <td><a href="{{ route('superadmin.companies.show', $company) }}">{{ $company->name }}</a></td>
                                <td class="sa-muted">{{ $company->owner?->name ?? '—' }}</td>
                                <td class="sa-muted">{{ $company->plan?->name ?? '—' }}</td>
                                <td>{{ $company->users_count }}</td>
                                <td>
                                    <span class="badge badge-{{ $company->status === 'active' ? 'active' : 'suspended' }}">
                                        {{ \App\Models\Company::STATUSES[$company->status] ?? ucfirst($company->status) }}
                                    </span>
                                </td>
                                <td class="sa-muted">{{ $company->created_at?->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div class="col-lg-4">
        <div class="sa-card">
            <h2 class="h5 mb-3 text-white">System health</h2>
            @foreach ($health as $item)
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <div class="text-white">{{ $item['label'] }}</div>
                        <div class="sa-muted small">{{ $item['detail'] }}</div>
                    </div>
                    <span class="sa-health-{{ $item['status'] }} small text-uppercase font-weight-bold">{{ $item['status'] }}</span>
                </div>
            @endforeach
        </div>

        <div class="sa-card">
            <h2 class="h5 mb-3 text-white">Platform alerts</h2>
            @forelse ($alerts as $alert)
                <div class="sa-alert-item {{ $alert['severity'] }}">
                    <div class="text-white">{{ $alert['title'] }}</div>
                    <div class="sa-muted small">{{ $alert['message'] }}</div>
                </div>
            @empty
                <div class="sa-empty py-3">
                    <p class="sa-empty__text mb-0">No alerts right now.</p>
                </div>
            @endforelse
        </div>

        <div class="sa-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 mb-0 text-white">Contact inquiries</h2>
                <a href="{{ route('superadmin.contact-inquiries.index') }}" class="btn btn-sm btn-outline-light">View all</a>
            </div>
            @forelse ($recentContactInquiries as $inquiry)
                <a href="{{ route('superadmin.contact-inquiries.show', $inquiry) }}" class="sa-activity-item d-block text-decoration-none">
                    <div class="d-flex justify-content-between align-items-start" style="gap: 0.5rem;">
                        <div>
                            <div class="text-white">
                                {{ $inquiry->name }}
                                <span class="badge badge-{{ $inquiry->isDemo() ? 'info' : 'secondary' }} ml-1">{{ $inquiry->intentLabel() }}</span>
                                @if ($inquiry->isNew())
                                    <span class="badge badge-warning ml-1">New</span>
                                @endif
                            </div>
                            <div class="sa-muted small">{{ $inquiry->email }}{{ $inquiry->company ? ' · '.$inquiry->company : '' }}</div>
                        </div>
                        <div class="sa-muted small text-nowrap">{{ $inquiry->created_at?->diffForHumans() }}</div>
                    </div>
                </a>
            @empty
                <div class="sa-empty py-3">
                    <p class="sa-empty__text mb-0">No website inquiries yet.</p>
                </div>
            @endforelse
        </div>

        <div class="sa-card">
            <h2 class="h5 mb-3 text-white">Recent activity</h2>
            @forelse ($recentActivity as $log)
                <div class="sa-activity-item">
                    <div class="text-white">
                        {{ $log->relatedCompanyName() }}
                        <span class="sa-muted">·</span>
                        {{ $log->actionLabel() }}
                    </div>
                    <div class="sa-muted small">
                        {{ $log->actor?->name ?? 'System' }}
                        · {{ $log->created_at?->diffForHumans() }}
                    </div>
                </div>
            @empty
                <div class="sa-empty py-3">
                    <p class="sa-empty__text mb-0">No activity logged yet.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.0/Chart.min.js"></script>
<script>
(function () {
    const charts = @json($charts);
    const gridColor = 'rgba(148, 163, 184, 0.15)';
    const textColor = '#94a3b8';

    function lineChart(canvasId, payload, color, fillColor) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;
        new Chart(canvas.getContext('2d'), {
            type: 'line',
            data: {
                labels: payload.labels,
                datasets: [{
                    label: 'Count',
                    data: payload.values,
                    borderColor: color,
                    backgroundColor: fillColor,
                    pointRadius: 2,
                    lineTension: 0.25,
                    fill: true,
                }]
            },
            options: {
                legend: { display: false },
                scales: {
                    xAxes: [{ gridLines: { color: gridColor }, ticks: { fontColor: textColor, maxTicksLimit: 6 } }],
                    yAxes: [{ gridLines: { color: gridColor }, ticks: { fontColor: textColor, beginAtZero: true, precision: 0 } }]
                }
            }
        });
    }

    lineChart('sa-companies-chart', charts.companies, 'rgba(56, 189, 248, 1)', 'rgba(56, 189, 248, 0.15)');
    lineChart('sa-leads-chart', charts.leads, 'rgba(52, 211, 153, 1)', 'rgba(52, 211, 153, 0.15)');
    lineChart('sa-customers-chart', charts.customers, 'rgba(251, 191, 36, 1)', 'rgba(251, 191, 36, 0.15)');
})();
</script>
@endpush
