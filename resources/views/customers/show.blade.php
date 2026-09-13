<x-app-layout>
    <x-slot name="header">
        <x-page-header
            :title="$customer->name"
            :subtitle="$customer->company_name ?? 'Customer profile'"
            :breadcrumbs="[
                ['label' => 'Home', 'url' => route('dashboard')],
                ['label' => 'Customers', 'url' => route('customers.index')],
                ['label' => $customer->name],
            ]"
        >
            <x-slot:actions>
                @can('update', $customer)
                    <a href="{{ route('customers.edit', $customer) }}" class="btn btn-default btn-sm">
                        <i class="fas fa-edit" aria-hidden="true"></i> Edit
                    </a>
                @endcan
                <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left" aria-hidden="true"></i> Back to customers
                </a>
            </x-slot:actions>
        </x-page-header>
    </x-slot>

    <div class="row">
        <div class="col-lg-4">
            <div class="card card-outline card-success">
                <div class="card-header">
                    <h3 class="card-title">Customer details</h3>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Status</dt>
                        <dd class="col-sm-8">
                            <span class="badge badge-{{ $customer->status === 'active' ? 'success' : 'secondary' }}">
                                {{ ucfirst($customer->status) }}
                            </span>
                        </dd>

                        <dt class="col-sm-4">Email</dt>
                        <dd class="col-sm-8">
                            @if ($customer->email)
                                <a href="mailto:{{ $customer->email }}">{{ $customer->email }}</a>
                            @else
                                —
                            @endif
                        </dd>

                        <dt class="col-sm-4">Phone</dt>
                        <dd class="col-sm-8">
                            @if ($customer->phone)
                                <a href="tel:{{ preg_replace('/[^\d+]/', '', $customer->phone) }}">{{ $customer->phone }}</a>
                            @else
                                —
                            @endif
                        </dd>

                        <dt class="col-sm-4">Company</dt>
                        <dd class="col-sm-8">{{ $customer->company_name ?? '—' }}</dd>

                        <dt class="col-sm-4">Address</dt>
                        <dd class="col-sm-8 crm-prewrap">{{ $customer->address ?: '—' }}</dd>

                        @if ($customer->sourceLead)
                            <dt class="col-sm-4">Source lead</dt>
                            <dd class="col-sm-8">
                                @can('view', $customer->sourceLead)
                                    <a href="{{ route('leads.show', $customer->sourceLead) }}">
                                        {{ $customer->sourceLead->name }}
                                    </a>
                                @else
                                    {{ $customer->sourceLead->name }}
                                @endcan
                            </dd>
                        @endif

                        <dt class="col-sm-4">Created by</dt>
                        <dd class="col-sm-8">{{ $customer->creator?->name ?? '—' }}</dd>

                        <dt class="col-sm-4">Created</dt>
                        <dd class="col-sm-8">{{ $customer->created_at?->format('M j, Y g:i A') ?? '—' }}</dd>
                    </dl>

                    <hr>
                    <p class="text-muted small mb-1">Notes</p>
                    @if (filled($customer->notes))
                        <p class="mb-0 crm-prewrap">{{ $customer->notes }}</p>
                    @else
                        <p class="mb-0 text-muted">No notes.</p>
                    @endif
                </div>
            </div>

            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Related tasks</h3>
                </div>
                <div class="card-body p-0">
                    @if ($tasks->isEmpty())
                        <div class="p-3">
                            <x-empty-state
                                class="crm-empty--compact"
                                icon="fas fa-tasks"
                                title="No related tasks"
                                description="Tasks linked to this customer will appear here."
                                :action-url="auth()->user()->can('create', App\Models\Task::class) ? route('tasks.create', ['customer_id' => $customer->id]) : null"
                                :action-label="auth()->user()->can('create', App\Models\Task::class) ? 'Add task' : null"
                            />
                        </div>
                    @else
                        <ul class="list-group list-group-flush">
                            @foreach ($tasks as $task)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        @can('view', $task)
                                            <a href="{{ route('tasks.show', $task) }}">{{ $task->title }}</a>
                                        @else
                                            {{ $task->title }}
                                        @endcan
                                        <div class="small text-muted">
                                            {{ ucfirst(str_replace('_', ' ', $task->status)) }}
                                            · {{ $task->assignee?->name ?? 'Unassigned' }}
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

            @can('delete', $customer)
                <div class="card card-outline card-danger">
                    <div class="card-body d-flex flex-wrap align-items-center justify-content-between">
                        <div class="mb-2 mb-md-0 pr-2">
                            <strong class="d-block">Delete customer</strong>
                            <span class="text-muted small">Permanently remove this customer record.</span>
                        </div>
                        <form method="POST" action="{{ route('customers.destroy', $customer) }}">
                            @csrf
                            @method('DELETE')
                            <button
                                type="submit"
                                class="btn btn-danger btn-sm"
                                data-crm-confirm="Delete this customer? This cannot be undone."
                                data-crm-confirm-title="Delete customer"
                                data-crm-confirm-label="Delete"
                            >
                                <i class="fas fa-trash" aria-hidden="true"></i> Delete customer
                            </button>
                        </form>
                    </div>
                </div>
            @endcan
        </div>

        <div class="col-lg-8">
            @include('customers.partials.timeline', ['timeline' => $timeline])
        </div>
    </div>
</x-app-layout>
