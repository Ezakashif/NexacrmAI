@extends('superadmin.layout')

@section('title', 'Subscription Plans')
@section('heading', 'Subscription Plans')
@section('subheading', 'Manage platform pricing, capabilities, and usage limits')

@section('content')
<div class="sa-card">
    <form method="GET" class="form-row align-items-end">
        <div class="form-group col-md-4 mb-md-0">
            <label class="sa-muted">Search</label>
            <input class="form-control" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Plan name or slug">
        </div>
        <div class="form-group col-md-2 mb-md-0">
            <label class="sa-muted">Status</label>
            <select class="custom-select" name="status"><option value="">All</option><option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option><option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option></select>
        </div>
        <div class="form-group col-md-2 mb-md-0">
            <label class="sa-muted">Type</label>
            <select class="custom-select" name="type"><option value="">All</option>@foreach (['free' => 'Free', 'paid' => 'Paid', 'featured' => 'Featured', 'public' => 'Public', 'hidden' => 'Hidden'] as $value => $label)<option value="{{ $value }}" @selected(($filters['type'] ?? '') === $value)>{{ $label }}</option>@endforeach</select>
        </div>
        <div class="form-group col-md-4 mb-0"><button class="btn btn-outline-light mr-2">Filter</button><a class="btn btn-outline-light mr-2" href="{{ route('superadmin.plans.export') }}">Export CSV</a><a class="btn btn-info" href="{{ route('superadmin.plans.create') }}">New plan</a></div>
    </form>
</div>
<div class="sa-card"><form method="POST" action="{{ route('superadmin.plans.import') }}" enctype="multipart/form-data" class="form-row align-items-end">@csrf<div class="form-group col-md-6 mb-md-0"><label class="sa-muted">Import plans CSV</label><input class="form-control-file" type="file" name="file" accept=".csv,text/csv" required><small class="sa-muted">Use the export column headings. Existing slugs are skipped.</small></div><div class="form-group col-md-4 mb-md-0"><button class="btn btn-outline-light mr-2">Import CSV</button><a class="btn btn-sm btn-outline-light" href="{{ route('superadmin.plans.import.sample') }}"><i class="fas fa-download" aria-hidden="true"></i> Download sample CSV</a></div></form></div>
<div class="sa-card">
    <form method="POST" action="{{ route('superadmin.plans.bulk') }}">@csrf
    <div class="d-flex align-items-center mb-3"><select name="action" class="custom-select mr-2" style="max-width: 220px;"><option value="">Bulk action…</option><option value="activate">Activate selected</option><option value="deactivate">Deactivate selected</option><option value="delete">Delete selected</option></select><button class="btn btn-outline-light" onclick="return confirm('Apply this action to all selected plans?')">Apply</button></div>
    <div class="table-responsive"><table class="table mb-0"><thead><tr><th><span class="sr-only">Select</span></th><th>Plan</th><th>Monthly</th><th>Yearly</th><th>Trial</th><th>Features</th><th>Limits</th><th>Status</th><th>Visibility</th><th>Created</th><th>Actions</th></tr></thead><tbody>
    @forelse ($plans as $plan)
        <tr><td><input type="checkbox" name="plan_ids[]" value="{{ $plan->id }}" aria-label="Select {{ $plan->name }}"></td><td><strong class="text-white">{{ $plan->name }}</strong><div class="sa-muted small">{{ $plan->slug }}</div></td><td>{{ $plan->currency }} {{ number_format((float) $plan->monthly_price, 2) }}</td><td>{{ $plan->currency }} {{ number_format((float) $plan->yearly_price, 2) }}</td><td>{{ $plan->trial_days }} days</td><td>{{ $plan->features_count }}</td><td>{{ $plan->limits_count }}</td><td><span class="badge badge-{{ $plan->is_active ? 'active' : 'suspended' }}">{{ $plan->is_active ? 'Active' : 'Inactive' }}</span></td><td>{{ $plan->is_public ? 'Public' : 'Hidden' }}</td><td class="sa-muted">{{ $plan->created_at?->format('Y-m-d') }}</td><td><a class="btn btn-sm btn-outline-light" href="{{ route('superadmin.plans.edit', $plan) }}">Edit</a></td></tr>
    @empty
        <tr><td colspan="11"><div class="sa-empty"><h2 class="sa-empty__title">No subscription plans found</h2><p class="sa-empty__text">Create a plan to control pricing, features, and limits.</p></div></td></tr>
    @endforelse
    </tbody></table></div></form>
    <div class="mt-3">{{ $plans->links() }}</div>
</div>
@endsection
