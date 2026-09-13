@extends('superadmin.layout')

@section('title', $company->name)
@section('heading', $company->name)
@section('subheading')
    {{ $company->slug }}
    @if ($company->trashed())
        · Deleted
    @endif
@endsection

@section('content')
@if ($company->trashed())
    <div class="alert alert-warning d-flex flex-wrap align-items-center justify-content-between">
        <div class="mb-2 mb-md-0">This company is soft-deleted. Review the profile or restore it to make it active again.</div>
        <form method="POST" action="{{ route('superadmin.companies.restore', $company->id) }}" class="mb-0">
            @csrf
            <button type="submit" class="btn btn-sm btn-info">Restore company</button>
        </form>
    </div>
@endif

<div class="row">
    <div class="col-lg-4">
        <div class="sa-card">
            <div class="d-flex align-items-center mb-3">
                @if ($company->logoUrl())
                    <img src="{{ $company->logoUrl() }}" alt="{{ $company->name }}" style="width:64px;height:64px;object-fit:cover;border-radius:0.5rem;margin-right:0.75rem;">
                @else
                    <div class="d-flex align-items-center justify-content-center mr-3" style="width:64px;height:64px;border-radius:0.5rem;background:#0b1220;border:1px solid #1f2937;color:#94a3b8;">
                        <i class="fas fa-building"></i>
                    </div>
                @endif
                <div>
                    <div class="text-white font-weight-bold">
                        {{ $company->name }}
                        @if ($company->trashed())
                            <span class="badge badge-secondary ml-1">Deleted</span>
                        @endif
                    </div>
                    <div class="sa-muted small">{{ $company->slug }}</div>
                </div>
            </div>

            <div class="mb-2">
                <span class="badge badge-{{ $company->status === 'active' ? 'active' : 'suspended' }}">
                    {{ $statuses[$company->status] ?? ucfirst($company->status) }}
                </span>
                <span class="badge badge-{{ $company->subscription_status === 'trial' ? 'trial' : ($company->subscription_status === 'expired' ? 'expired' : 'active') }}">
                    {{ $subscriptionStatuses[$company->subscription_status] ?? ucfirst((string) $company->subscription_status) }}
                </span>
            </div>

            <dl class="mb-0 small">
                <dt class="sa-muted">Owner</dt>
                <dd class="text-white">{{ $company->owner?->name ?? '—' }}</dd>
                <dt class="sa-muted">Email</dt>
                <dd class="text-white">{{ $company->email ?? $company->owner?->email ?? '—' }}</dd>
                <dt class="sa-muted">Phone</dt>
                <dd class="text-white">{{ $company->phone ?? '—' }}</dd>
                <dt class="sa-muted">Plan</dt>
                <dd class="text-white">{{ $company->plan?->name ?? '—' }}</dd>
                <dt class="sa-muted">Trial expiry</dt>
                <dd class="text-white">{{ $company->trial_ends_at?->toDayDateTimeString() ?? '—' }}</dd>
                <dt class="sa-muted">Created</dt>
                <dd class="text-white">{{ $company->created_at?->toDayDateTimeString() }}</dd>
                <dt class="sa-muted">Last active</dt>
                <dd class="text-white">{{ $company->last_active_at?->diffForHumans() ?? 'Never' }}</dd>
                <dt class="sa-muted">Last login</dt>
                <dd class="text-white">{{ $lastLogin ? \Illuminate\Support\Carbon::parse($lastLogin)->diffForHumans() : 'Never' }}</dd>
            </dl>

            <div class="d-flex flex-wrap mt-3">
                @if ($company->trashed())
                    <form method="POST" action="{{ route('superadmin.companies.restore', $company->id) }}" class="mb-2">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-info">Restore</button>
                    </form>
                    <a href="{{ route('superadmin.companies.index', ['trashed' => 1]) }}" class="btn btn-sm btn-outline-light mb-2 ml-2">Back to deleted</a>
                @else
                    <a href="{{ route('superadmin.companies.edit', $company) }}" class="btn btn-sm btn-outline-light mr-2 mb-2">Edit</a>
                    <a href="{{ route('superadmin.companies.pdf', $company) }}" class="btn btn-sm btn-outline-light mr-2 mb-2">Export PDF</a>

                    @if ($company->status === 'active' && ! $company->isDefault())
                        <form method="POST" action="{{ route('superadmin.companies.status', $company) }}" class="mr-2 mb-2">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="suspended">
                            <button
                                type="submit"
                                class="btn btn-sm btn-danger"
                                data-sa-confirm="Suspend this company? Users will be blocked from the CRM."
                                data-sa-confirm-title="Suspend company"
                                data-sa-confirm-label="Suspend"
                            >
                                Suspend
                            </button>
                        </form>
                    @elseif ($company->status !== 'active')
                        <form method="POST" action="{{ route('superadmin.companies.status', $company) }}" class="mr-2 mb-2">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="active">
                            <button class="btn btn-sm btn-success" type="submit">Activate</button>
                        </form>
                    @endif

                    @if ($company->status === 'active')
                        <form method="POST" action="{{ route('superadmin.companies.impersonate', $company) }}" class="mr-2 mb-2">
                            @csrf
                            <button
                                type="submit"
                                class="btn btn-sm btn-info"
                                data-sa-confirm="Login as company admin? You will enter their CRM workspace."
                                data-sa-confirm-title="Login as admin"
                                data-sa-confirm-label="Login as"
                                data-sa-confirm-class="btn-info"
                            >Login As Admin</button>
                        </form>
                    @endif

                    @if (! $company->isDefault())
                        <form method="POST" action="{{ route('superadmin.companies.destroy', $company) }}" class="mb-2">
                            @csrf
                            @method('DELETE')
                            <button
                                type="submit"
                                class="btn btn-sm btn-outline-danger"
                                data-sa-confirm="Soft-delete this company? It can be restored later from the deleted list."
                                data-sa-confirm-title="Delete company"
                                data-sa-confirm-label="Delete"
                            >Delete</button>
                        </form>
                    @endif
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="row">
            @foreach ([
                'users' => 'Total Users',
                'leads' => 'Leads',
                'customers' => 'Customers',
                'tasks' => 'Tasks',
                'activities' => 'Activities',
            ] as $key => $label)
                <div class="col-sm-4">
                    <div class="sa-card">
                        <div class="sa-muted mb-1">{{ $label }}</div>
                        <div class="sa-stat">{{ number_format($usage[$key]) }}</div>
                    </div>
                </div>
            @endforeach
            <div class="col-sm-4">
                <div class="sa-card">
                    <div class="sa-muted mb-1">Storage usage</div>
                    <div class="sa-stat" style="font-size:1.25rem;">
                        @php
                            $bytes = $usage['storage_bytes'];
                            $units = ['B','KB','MB','GB'];
                            $i = 0; $value = (float) $bytes;
                            while ($value >= 1024 && $i < count($units) - 1) { $value /= 1024; $i++; }
                        @endphp
                        {{ round($value, 1) }} {{ $units[$i] }}
                    </div>
                </div>
            </div>
        </div>

        <div class="sa-card">
            <h2 class="h6 text-white mb-3">Usage overview</h2>
            @if ($company->plan)
                <div class="row small">
                    <div class="col-md-4 mb-2">
                        <div class="sa-muted">Users</div>
                        <div class="text-white">{{ $usage['users'] }} / {{ $company->plan->max_users ?? '∞' }}</div>
                    </div>
                    <div class="col-md-4 mb-2">
                        <div class="sa-muted">Leads</div>
                        <div class="text-white">{{ $usage['leads'] }} / {{ $company->plan->max_leads ?? '∞' }}</div>
                    </div>
                    <div class="col-md-4 mb-2">
                        <div class="sa-muted">Customers</div>
                        <div class="text-white">{{ $usage['customers'] }} / {{ $company->plan->max_customers ?? '∞' }}</div>
                    </div>
                </div>
            @else
                <p class="sa-muted mb-0">No plan assigned.</p>
            @endif
        </div>

        <div class="sa-card">
            <h2 class="h6 text-white mb-3">Users</h2>
            @if ($users->isEmpty())
                <div class="sa-empty py-4">
                    <div class="sa-empty__icon" aria-hidden="true"><i class="fas fa-users"></i></div>
                    <h3 class="sa-empty__title">No users yet</h3>
                    <p class="sa-empty__text mb-0">This company has no users provisioned.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Roles</th>
                            <th>Status</th>
                            <th>Last login</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($users as $user)
                            <tr>
                                <td class="text-white">{{ $user->name }}</td>
                                <td class="sa-muted">{{ $user->email }}</td>
                                <td class="sa-muted">{{ $user->roles->pluck('name')->join(', ') ?: '—' }}</td>
                                <td>{{ $user->status }}</td>
                                <td class="sa-muted">{{ $user->last_login_at?->diffForHumans() ?? 'Never' }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="sa-card">
            <h2 class="h6 text-white mb-3">Recent activity</h2>
            @forelse ($recentActivity as $log)
                <div class="sa-activity-item">
                    <div class="text-white">{{ $log->actionLabel() }}</div>
                    <div class="sa-muted small">
                        {{ $log->actor?->name ?? 'System' }}
                        · {{ $log->module() }}
                        · {{ $log->created_at?->diffForHumans() }}
                        @if ($log->ip_address)
                            · {{ $log->ip_address }}
                        @endif
                    </div>
                    <div class="sa-muted small">{{ $log->description() }}</div>
                </div>
            @empty
                <div class="sa-empty py-3">
                    <p class="sa-empty__text mb-0">No recent activity.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
