@extends('superadmin.layout')

@section('title', 'Companies')
@section('heading', 'Companies')
@section('subheading', 'Manage tenant organizations across the platform')

@section('content')
@if (session('import_errors'))
    <div class="alert alert-warning">
        <strong>Import notes:</strong>
        <ul class="mb-0 pl-3">
            @foreach (session('import_errors') as $error)
                <li>Row {{ $error['row'] }}: {{ $error['message'] }}</li>
            @endforeach
        </ul>
    </div>
@endif

@php
    $hasActiveFilters = collect($filters ?? [])
        ->filter(fn ($value) => filled($value))
        ->isNotEmpty();
@endphp

<div class="sa-toolbar">
    <div class="sa-toolbar__meta">
        <span class="sa-toolbar__count">{{ $companies->total() }} {{ \Illuminate\Support\Str::plural('company', $companies->total()) }}</span>
        @if ($hasActiveFilters)
            <span class="sa-toolbar__hint">Filtered results</span>
        @endif
    </div>
    <div class="sa-toolbar__actions">
        <div class="btn-group sa-btn-group" role="group" aria-label="Import and export">
            <a href="{{ route('superadmin.companies.import.create') }}" class="btn btn-sm btn-outline-light">
                <i class="fas fa-file-import" aria-hidden="true"></i> Import
            </a>
            <a href="{{ route('superadmin.companies.export', request()->query()) }}" class="btn btn-sm btn-outline-light">
                <i class="fas fa-file-csv" aria-hidden="true"></i> Export CSV
            </a>
            <a href="{{ route('superadmin.companies.export.pdf', request()->query()) }}" class="btn btn-sm btn-outline-light">
                <i class="fas fa-file-pdf" aria-hidden="true"></i> Export PDF
            </a>
        </div>
        <a href="{{ route('superadmin.companies.create') }}" class="btn btn-sm btn-info">
            <i class="fas fa-plus" aria-hidden="true"></i> New company
        </a>
    </div>
</div>

<div class="sa-card sa-filter-bar">
    <form method="GET" action="{{ route('superadmin.companies.index') }}">
        <div class="sa-filter-bar__grid">
            <div class="sa-filter-bar__field sa-filter-bar__field--search">
                <label class="sa-filter-bar__label" for="companies-search">Search</label>
                <input
                    id="companies-search"
                    type="search"
                    name="search"
                    value="{{ $filters['search'] ?? '' }}"
                    class="form-control form-control-sm"
                    placeholder="Name, slug, email, owner"
                >
            </div>
            <div class="sa-filter-bar__field">
                <label class="sa-filter-bar__label" for="companies-status">Status</label>
                <select id="companies-status" name="status" class="custom-select custom-select-sm">
                    <option value="">All</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sa-filter-bar__field">
                <label class="sa-filter-bar__label" for="companies-subscription">Subscription</label>
                <select id="companies-subscription" name="subscription_status" class="custom-select custom-select-sm">
                    <option value="">All</option>
                    @foreach ($subscriptionStatuses as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['subscription_status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sa-filter-bar__field">
                <label class="sa-filter-bar__label" for="companies-plan">Plan</label>
                <select id="companies-plan" name="plan_id" class="custom-select custom-select-sm">
                    <option value="">All</option>
                    @foreach ($plans as $plan)
                        <option value="{{ $plan->id }}" @selected((string) ($filters['plan_id'] ?? '') === (string) $plan->id)>{{ $plan->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="sa-filter-bar__footer">
            <div class="custom-control custom-checkbox mb-0">
                <input type="checkbox" class="custom-control-input" id="trashed" name="trashed" value="1" @checked(! empty($filters['trashed']))>
                <label class="custom-control-label sa-muted" for="trashed">Show deleted only</label>
            </div>
            <div class="sa-filter-bar__submit">
                @if ($hasActiveFilters)
                    <a href="{{ route('superadmin.companies.index') }}" class="btn btn-sm btn-outline-light">Clear</a>
                @endif
                <button type="submit" class="btn btn-sm btn-outline-light">
                    <i class="fas fa-filter" aria-hidden="true"></i> Apply filters
                </button>
            </div>
        </div>
    </form>
</div>

<div class="sa-card">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
            <tr>
                <th>Company Name</th>
                <th>Owner</th>
                <th>Plan</th>
                <th>Users</th>
                <th>Leads</th>
                <th>Customers</th>
                <th>Status</th>
                <th>Last Active</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($companies as $company)
                <tr class="{{ $company->trashed() ? 'table-secondary' : '' }}">
                    <td>
                        <div class="font-weight-bold text-white">
                            {{ $company->name }}
                            @if ($company->trashed())
                                <span class="badge badge-secondary ml-1">Deleted</span>
                            @endif
                        </div>
                        <div class="sa-muted small">{{ $company->slug }}</div>
                    </td>
                    <td>
                        <div class="text-white">{{ $company->owner?->name ?? '—' }}</div>
                        <div class="sa-muted small">{{ $company->owner?->email ?? $company->email ?? '—' }}</div>
                    </td>
                    <td class="sa-muted">{{ $company->plan?->name ?? '—' }}</td>
                    <td>{{ $company->users_count }}</td>
                    <td>{{ $company->leads_count }}</td>
                    <td>{{ $company->customers_count }}</td>
                    <td>
                        <span class="badge badge-{{ $company->status === 'active' ? 'active' : 'suspended' }}">
                            {{ $statuses[$company->status] ?? ucfirst($company->status) }}
                        </span>
                        <div class="sa-muted small mt-1">{{ $subscriptionStatuses[$company->subscription_status] ?? ucfirst((string) $company->subscription_status) }}</div>
                    </td>
                    <td class="sa-muted">{{ $company->last_active_at?->diffForHumans() ?? 'Never' }}</td>
                    <td class="sa-muted">{{ $company->created_at?->format('Y-m-d') }}</td>
                    <td>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-light dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Actions
                            </button>
                            <div class="dropdown-menu dropdown-menu-right">
                                @if ($company->trashed())
                                    <a class="dropdown-item" href="{{ route('superadmin.companies.show', $company->id) }}">View</a>
                                    <form method="POST" action="{{ route('superadmin.companies.restore', $company->id) }}">
                                        @csrf
                                        <button class="dropdown-item" type="submit">Restore</button>
                                    </form>
                                                @else
                                    <a class="dropdown-item" href="{{ route('superadmin.companies.show', $company) }}">View</a>
                                    <a class="dropdown-item" href="{{ route('superadmin.companies.edit', $company) }}">Edit</a>
                                    @if (! $company->isDefault())
                                        @if ($company->status === 'active')
                                            <form method="POST" action="{{ route('superadmin.companies.status', $company) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="suspended">
                                                <button
                                                    class="dropdown-item text-danger"
                                                    type="submit"
                                                    data-sa-confirm="Suspend this company? Users will be blocked from the CRM."
                                                    data-sa-confirm-title="Suspend company"
                                                    data-sa-confirm-label="Suspend"
                                                >Suspend</button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('superadmin.companies.status', $company) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="active">
                                                <button class="dropdown-item text-success" type="submit">Activate</button>
                                            </form>
                                        @endif
                                    @endif
                                    @if ($company->status === 'active')
                                        <form method="POST" action="{{ route('superadmin.companies.impersonate', $company) }}">
                                            @csrf
                                            <button
                                                class="dropdown-item"
                                                type="submit"
                                                data-sa-confirm="Login as this company admin? You will enter their CRM workspace."
                                                data-sa-confirm-title="Login as admin"
                                                data-sa-confirm-label="Login as"
                                                data-sa-confirm-class="btn-info"
                                            >Login as</button>
                                        </form>
                                    @endif
                                    @if (! $company->isDefault())
                                        <div class="dropdown-divider"></div>
                                        <form method="POST" action="{{ route('superadmin.companies.destroy', $company) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                class="dropdown-item text-danger"
                                                type="submit"
                                                data-sa-confirm="Soft-delete this company? It can be restored later from the deleted list."
                                                data-sa-confirm-title="Delete company"
                                                data-sa-confirm-label="Delete"
                                            >Delete</button>
                                        </form>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10">
                        <div class="sa-empty">
                            <div class="sa-empty__icon" aria-hidden="true"><i class="fas fa-building"></i></div>
                            <h2 class="sa-empty__title">No companies found</h2>
                            <p class="sa-empty__text">
                                @if (! empty($filters['search']) || ! empty($filters['status']) || ! empty($filters['subscription_status']) || ! empty($filters['plan_id']) || ! empty($filters['trashed']))
                                    No tenants match your filters. Try clearing them or create a new company.
                                @else
                                    Provision your first tenant to get started.
                                @endif
                            </p>
                            <div class="d-flex justify-content-center flex-wrap" style="gap: 0.5rem;">
                                @if (! empty($filters['search']) || ! empty($filters['status']) || ! empty($filters['subscription_status']) || ! empty($filters['plan_id']) || ! empty($filters['trashed']))
                                    <a href="{{ route('superadmin.companies.index') }}" class="btn btn-sm btn-outline-light">Clear filters</a>
                                @endif
                                <a href="{{ route('superadmin.companies.create') }}" class="btn btn-sm btn-info">New company</a>
                            </div>
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if ($companies->hasPages())
        <div class="mt-3">
            {{ $companies->links() }}
        </div>
    @endif
</div>
@endsection
