<x-app-layout>
    @php
        $minLength = \App\Services\GlobalSearchService::MIN_TERM_LENGTH;
        $subtitle = $term !== ''
            ? 'Results for “'.$term.'”'
            : 'Find leads, customers, tasks, users, and companies.';
    @endphp

    <x-slot name="header">
        <x-page-header
            title="Search"
            :subtitle="$subtitle"
            :breadcrumbs="[
                ['label' => 'Home', 'url' => route('dashboard')],
                ['label' => 'Search'],
            ]"
        />
    </x-slot>

    <div class="card card-outline card-secondary mb-3 crm-filter-card">
        <div class="card-body py-3 px-3">
            <form method="GET" action="{{ route('search.index') }}" class="row align-items-end">
                <div class="col-md-8 mb-2">
                    <label for="search-q" class="small text-muted mb-1">Query</label>
                    <div class="input-group input-group-sm">
                        <input
                            id="search-q"
                            type="search"
                            name="q"
                            value="{{ $term }}"
                            class="form-control js-global-search"
                            placeholder="Name, email, phone, company, task, user..."
                            minlength="{{ $minLength }}"
                            maxlength="100"
                            autocomplete="off"
                            autofocus
                            aria-label="Search the CRM"
                        >
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-primary" aria-label="Run search">
                                <i class="fas fa-search" aria-hidden="true"></i> Search
                            </button>
                        </div>
                    </div>
                </div>
                @if ($term !== '')
                    <div class="col-md-auto mb-2">
                        <a href="{{ route('search.index') }}" class="btn btn-default btn-sm" aria-label="Clear search">
                            <i class="fas fa-times" aria-hidden="true"></i> Clear
                        </a>
                    </div>
                @endif
            </form>
        </div>
    </div>

    @if ($term === '')
        <div class="card">
            <div class="card-body">
                <x-empty-state
                    icon="fas fa-search"
                    title="Search the CRM"
                    :description="'Type at least '.$minLength.' characters to search leads, customers, tasks, users, and companies.'"
                />
            </div>
        </div>
    @elseif ($too_short)
        <div class="card">
            <div class="card-body">
                <x-empty-state
                    class="crm-empty--compact"
                    icon="fas fa-search"
                    title="Query too short"
                    :description="'Enter at least '.$minLength.' characters to search.'"
                    :action-url="route('search.index')"
                    action-label="Clear search"
                />
            </div>
        </div>
    @elseif ($total === 0)
        <div class="card">
            <div class="card-body">
                <x-empty-state
                    class="crm-empty--compact"
                    icon="fas fa-search"
                    title="No matches found"
                    :description="'No matches found for “'.$term.'”. Try a different name, email, or company.'"
                    :action-url="route('search.index')"
                    action-label="Clear search"
                />
            </div>
        </div>
    @else
        <p class="text-muted small mb-3">
            Showing up to {{ \App\Services\GlobalSearchService::PER_CATEGORY_LIMIT }} results per category
            ({{ $total }} shown).
        </p>

        @if ($can_view_leads)
            <div class="card card-outline card-secondary mb-3">
                <div class="card-header border-0 bg-transparent">
                    <h2 class="h6 mb-0 text-dark">
                        <i class="fas fa-funnel-dollar text-muted mr-1" aria-hidden="true"></i>
                        Leads
                        <span class="badge badge-light border ml-1">{{ $leads->count() }}</span>
                    </h2>
                </div>
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
                            @forelse ($leads as $lead)
                                <tr>
                                    <td class="font-weight-bold">{{ $lead->name }}</td>
                                    <td>{{ $lead->email ?? '—' }}</td>
                                    <td>{{ $lead->phone ?? '—' }}</td>
                                    <td>{{ $lead->company ?? '—' }}</td>
                                    <td>
                                        <span class="badge badge-secondary">{{ $lead->statusLabel() }}</span>
                                    </td>
                                    <td class="text-right text-nowrap">
                                        @can('view', $lead)
                                            <a href="{{ route('leads.show', $lead) }}" class="btn btn-xs btn-outline-primary">
                                                View
                                            </a>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-muted text-center">No matching leads.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if ($can_view_customers)
            <div class="card card-outline card-secondary mb-3">
                <div class="card-header border-0 bg-transparent">
                    <h2 class="h6 mb-0 text-dark">
                        <i class="fas fa-users text-muted mr-1" aria-hidden="true"></i>
                        Customers
                        <span class="badge badge-light border ml-1">{{ $customers->count() }}</span>
                    </h2>
                </div>
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
                            @forelse ($customers as $customer)
                                <tr>
                                    <td class="font-weight-bold">{{ $customer->name }}</td>
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
                                            <a href="{{ route('customers.show', $customer) }}" class="btn btn-xs btn-outline-primary">
                                                View
                                            </a>
                                        @endcan
                                        @can('update', $customer)
                                            <a href="{{ route('customers.edit', $customer) }}" class="btn btn-xs btn-outline-secondary">
                                                Edit
                                            </a>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-muted text-center">No matching customers.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if ($can_view_users)
            <div class="card card-outline card-secondary mb-3">
                <div class="card-header border-0 bg-transparent">
                    <h2 class="h6 mb-0 text-dark">
                        <i class="fas fa-user-shield text-muted mr-1" aria-hidden="true"></i>
                        Users
                        <span class="badge badge-light border ml-1">{{ $users->count() }}</span>
                    </h2>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover text-nowrap mb-0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Roles</th>
                                <th>Status</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($users as $resultUser)
                                <tr>
                                    <td class="font-weight-bold">{{ $resultUser->name }}</td>
                                    <td>{{ $resultUser->email }}</td>
                                    <td>{{ $resultUser->roleNames() ?: '—' }}</td>
                                    <td>
                                        <span class="badge badge-{{ $resultUser->statusBadgeClass() }}">
                                            {{ ucfirst($resultUser->status) }}
                                        </span>
                                    </td>
                                    <td class="text-right text-nowrap">
                                        @can('view', $resultUser)
                                            <a href="{{ route('users.show', $resultUser) }}" class="btn btn-xs btn-outline-primary">
                                                View
                                            </a>
                                        @endcan
                                        @can('update', $resultUser)
                                            <a href="{{ route('users.edit', $resultUser) }}" class="btn btn-xs btn-outline-secondary">
                                                Edit
                                            </a>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-muted text-center">No matching users.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if ($can_view_tasks)
            <div class="card card-outline card-secondary mb-3">
                <div class="card-header border-0 bg-transparent">
                    <h2 class="h6 mb-0 text-dark">
                        <i class="fas fa-tasks text-muted mr-1" aria-hidden="true"></i>
                        Tasks
                        <span class="badge badge-light border ml-1">{{ $tasks->count() }}</span>
                    </h2>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover text-nowrap mb-0">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Status</th>
                                <th>Priority</th>
                                <th>Assignee</th>
                                <th>Related</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($tasks as $task)
                                <tr>
                                    <td>
                                        <div class="font-weight-bold">{{ $task->title }}</div>
                                        @if (filled($task->description))
                                            <small class="text-muted">
                                                {{ \Illuminate\Support\Str::limit($task->description, 80) }}
                                            </small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-secondary">
                                            {{ ucfirst(str_replace('_', ' ', $task->status)) }}
                                        </span>
                                    </td>
                                    <td>{{ ucfirst($task->priority) }}</td>
                                    <td>{{ $task->assignee?->name ?? '—' }}</td>
                                    <td>{{ $task->customer?->name ?? $task->lead?->name ?? '—' }}</td>
                                    <td class="text-right text-nowrap">
                                        @can('view', $task)
                                            <a href="{{ route('tasks.show', $task) }}" class="btn btn-xs btn-outline-primary">
                                                View
                                            </a>
                                        @endcan
                                        @can('update', $task)
                                            <a href="{{ route('tasks.edit', $task) }}" class="btn btn-xs btn-outline-secondary">
                                                Edit
                                            </a>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-muted text-center">No matching tasks.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if ($can_view_leads || $can_view_customers)
            <div class="card card-outline card-secondary mb-0">
                <div class="card-header border-0 bg-transparent">
                    <h2 class="h6 mb-0 text-dark">
                        <i class="fas fa-building text-muted mr-1" aria-hidden="true"></i>
                        Companies
                        <span class="badge badge-light border ml-1">{{ count($companies) }}</span>
                    </h2>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover text-nowrap mb-0">
                        <thead>
                            <tr>
                                <th>Company</th>
                                <th>Found in</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($companies as $company)
                                <tr>
                                    <td class="font-weight-bold">{{ $company['name'] }}</td>
                                    <td>
                                        @foreach ($company['sources'] as $source)
                                            <span class="badge badge-light border">{{ ucfirst($source) }}</span>
                                        @endforeach
                                    </td>
                                    <td class="text-right text-nowrap">
                                        @if (in_array('leads', $company['sources'], true))
                                            <a href="{{ route('leads.index', ['search' => $company['name']]) }}"
                                               class="btn btn-xs btn-outline-secondary">
                                                Leads
                                            </a>
                                        @endif
                                        @if (in_array('customers', $company['sources'], true))
                                            <a href="{{ route('customers.index', ['search' => $company['name']]) }}"
                                               class="btn btn-xs btn-outline-secondary">
                                                Customers
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-muted text-center">No matching companies.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @endif
</x-app-layout>
