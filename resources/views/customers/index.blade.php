<x-app-layout>
    <x-slot name="header">
        <x-page-header
            title="Customers"
            subtitle="Accounts converted from won leads and direct adds."
            :breadcrumbs="[
                ['label' => 'Home', 'url' => route('dashboard')],
                ['label' => 'Customers'],
            ]"
        >
            <x-slot:actions>
                @can('viewAny', App\Models\Customer::class)
                    <a href="{{ route('exports.customers', request()->query()) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-file-download" aria-hidden="true"></i> Export CSV
                    </a>
                @endcan
                @can('create', App\Models\Customer::class)
                    <a href="{{ route('imports.create', 'customers') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-file-upload" aria-hidden="true"></i> Import CSV
                    </a>
                    <a href="{{ route('customers.create') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus" aria-hidden="true"></i> Add Customer
                    </a>
                @endcan
            </x-slot:actions>
        </x-page-header>
    </x-slot>

    @if (session('import_errors'))
        <div class="alert alert-warning alert-dismissible crm-keep-alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Dismiss">&times;</button>
            <strong>Import notes</strong>
            <ul class="mb-0 mt-2">
                @foreach (session('import_errors') as $error)
                    <li>Row {{ $error['row'] }}: {{ $error['message'] }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <x-list-filters :reset-url="route('customers.index')">
        <div class="col-md-4 mb-2">
            <label for="search" class="small text-muted mb-1">Search</label>
            <input id="search" name="search" type="text" class="form-control form-control-sm"
                   placeholder="Name, email, phone, company..."
                   value="{{ $filters['search'] ?? '' }}">
        </div>
        <div class="col-md-3 mb-2">
            <label for="status" class="small text-muted mb-1">Status</label>
            <select id="status" name="status" class="form-control form-control-sm">
                <option value="">All statuses</option>
                <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option>
            </select>
        </div>
    </x-list-filters>

    @php
        $hasFilters = collect($filters ?? [])->filter(fn ($value) => filled($value))->isNotEmpty();
        $canCreateCustomer = auth()->user()->can('create', App\Models\Customer::class);
    @endphp

    <div class="card">
        @if ($customers->isEmpty())
            <div class="card-body">
                <x-empty-state
                    class="crm-empty--compact"
                    icon="fas fa-building"
                    :title="$hasFilters ? 'No customers match your filters' : 'No customers yet'"
                    :description="$hasFilters
                        ? 'Try clearing filters or broadening your search.'
                        : 'Convert a won lead or add a customer to get started.'"
                    :action-url="$hasFilters ? route('customers.index') : ($canCreateCustomer ? route('customers.create') : null)"
                    :action-label="$hasFilters ? 'Clear filters' : ($canCreateCustomer ? 'Add customer' : null)"
                />
            </div>
        @else
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Company</th>
                            <th>Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($customers as $customer)
                            <tr>
                                <td>
                                    @can('view', $customer)
                                        <a href="{{ route('customers.show', $customer) }}">{{ $customer->name }}</a>
                                    @else
                                        {{ $customer->name }}
                                    @endcan
                                </td>
                                <td>{{ $customer->email ?? '—' }}</td>
                                <td>{{ $customer->phone ?? '—' }}</td>
                                <td>{{ $customer->company_name ?? '—' }}</td>
                                <td>
                                    <span class="badge badge-{{ $customer->status === 'active' ? 'success' : 'secondary' }}">
                                        {{ ucfirst($customer->status) }}
                                    </span>
                                </td>
                                <td class="text-right text-nowrap">
                                    @can('view', $customer)
                                        <a href="{{ route('customers.show', $customer) }}" class="btn btn-xs btn-primary" title="View" aria-label="View {{ $customer->name }}">
                                            <i class="fas fa-eye" aria-hidden="true"></i>
                                        </a>
                                    @endcan
                                    @can('update', $customer)
                                        <a href="{{ route('customers.edit', $customer) }}" class="btn btn-xs btn-default" title="Edit" aria-label="Edit {{ $customer->name }}">
                                            <i class="fas fa-edit" aria-hidden="true"></i>
                                        </a>
                                    @endcan
                                    @can('delete', $customer)
                                        <form method="POST" action="{{ route('customers.destroy', $customer) }}" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="submit"
                                                class="btn btn-xs btn-danger"
                                                title="Delete"
                                                aria-label="Delete {{ $customer->name }}"
                                                data-crm-confirm="Delete this customer? This cannot be undone."
                                                data-crm-confirm-title="Delete customer"
                                                data-crm-confirm-label="Delete"
                                            >
                                                <i class="fas fa-trash" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($customers->hasPages())
                <div class="card-footer clearfix">
                    {{ $customers->links() }}
                </div>
            @endif
        @endif
    </div>
</x-app-layout>
