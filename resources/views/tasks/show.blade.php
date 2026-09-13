<x-app-layout>
    <x-slot name="header">
        <x-page-header
            :title="$task->title"
            subtitle="Task details"
            :breadcrumbs="[
                ['label' => 'Home', 'url' => route('dashboard')],
                ['label' => 'Tasks', 'url' => route('tasks.index')],
                ['label' => $task->title],
            ]"
        >
            <x-slot:actions>
                @can('update', $task)
                    <a href="{{ route('tasks.edit', $task) }}" class="btn btn-default btn-sm">
                        <i class="fas fa-edit" aria-hidden="true"></i> Edit
                    </a>
                @endcan
                <a href="{{ route('tasks.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left" aria-hidden="true"></i> Back to board
                </a>
            </x-slot:actions>
        </x-page-header>
    </x-slot>

    @php
        $statusBadge = match ($task->status) {
            'pending' => 'warning',
            'in_progress' => 'info',
            'completed' => 'success',
            'cancelled' => 'secondary',
            default => 'secondary',
        };

        $priorityBadge = match ($task->priority) {
            'low' => 'secondary',
            'medium' => 'primary',
            'high' => 'warning',
            'urgent' => 'danger',
            default => 'secondary',
        };
    @endphp

    <div class="row">
        <div class="col-lg-8">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Task details</h3>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Status</dt>
                        <dd class="col-sm-8">
                            <span class="badge badge-{{ $statusBadge }}">
                                {{ ucfirst(str_replace('_', ' ', $task->status)) }}
                            </span>
                        </dd>

                        <dt class="col-sm-4">Priority</dt>
                        <dd class="col-sm-8">
                            <span class="badge badge-{{ $priorityBadge }}">
                                {{ ucfirst($task->priority) }}
                            </span>
                        </dd>

                        <dt class="col-sm-4">Assigned to</dt>
                        <dd class="col-sm-8">{{ $task->assignee?->name ?? 'Unassigned' }}</dd>

                        <dt class="col-sm-4">Created by</dt>
                        <dd class="col-sm-8">{{ $task->creator?->name ?? '—' }}</dd>

                        <dt class="col-sm-4">Due date</dt>
                        <dd class="col-sm-8">
                            @if ($task->due_date)
                                {{ \Carbon\Carbon::parse($task->due_date)->format('M j, Y') }}
                            @else
                                —
                            @endif
                        </dd>

                        <dt class="col-sm-4">Completed at</dt>
                        <dd class="col-sm-8">
                            @if ($task->completed_at)
                                {{ \Carbon\Carbon::parse($task->completed_at)->format('M j, Y g:i A') }}
                            @else
                                —
                            @endif
                        </dd>

                        <dt class="col-sm-4">Related lead</dt>
                        <dd class="col-sm-8">
                            @if ($task->lead)
                                @can('view', $task->lead)
                                    <a href="{{ route('leads.show', $task->lead) }}">{{ $task->lead->name }}</a>
                                @else
                                    {{ $task->lead->name }}
                                @endcan
                            @else
                                —
                            @endif
                        </dd>

                        <dt class="col-sm-4">Related customer</dt>
                        <dd class="col-sm-8">
                            @if ($task->customer)
                                @can('view', $task->customer)
                                    <a href="{{ route('customers.show', $task->customer) }}">{{ $task->customer->name }}</a>
                                @else
                                    {{ $task->customer->name }}
                                @endcan
                            @else
                                —
                            @endif
                        </dd>
                    </dl>

                    <hr>
                    <p class="text-muted small mb-1">Description</p>
                    @if (filled($task->description))
                        <p class="mb-0 crm-prewrap">{{ $task->description }}</p>
                    @else
                        <p class="mb-0 text-muted">No description.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            @can('changeStatus', $task)
                <div class="card card-outline card-secondary">
                    <div class="card-header">
                        <h3 class="card-title">Update status</h3>
                    </div>
                    <form method="POST" action="{{ route('tasks.status', $task) }}">
                        @csrf
                        <div class="card-body">
                            <div class="form-group mb-0">
                                <x-form-label for="status">Status</x-form-label>
                                <select id="status" name="status" class="form-control" onchange="this.form.submit()" aria-label="Update task status">
                                    <option value="pending" @selected($task->status === 'pending')>Pending</option>
                                    <option value="in_progress" @selected($task->status === 'in_progress')>In Progress</option>
                                    <option value="completed" @selected($task->status === 'completed')>Completed</option>
                                    <option value="cancelled" @selected($task->status === 'cancelled')>Cancelled</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
            @endcan

            <div class="card card-outline card-secondary">
                <div class="card-body">
                    <p class="text-muted small mb-1">Created</p>
                    <p class="mb-3">{{ $task->created_at?->format('M j, Y g:i A') ?? '—' }}</p>
                    <p class="text-muted small mb-1">Last updated</p>
                    <p class="mb-0">{{ $task->updated_at?->format('M j, Y g:i A') ?? '—' }}</p>
                </div>
            </div>

            @can('delete', $task)
                <div class="card card-outline card-danger">
                    <div class="card-body d-flex flex-wrap align-items-center justify-content-between">
                        <div class="mb-2 mb-md-0 pr-2">
                            <strong class="d-block">Delete task</strong>
                            <span class="text-muted small">Permanently remove this task.</span>
                        </div>
                        <form method="POST" action="{{ route('tasks.destroy', $task) }}">
                            @csrf
                            @method('DELETE')
                            <button
                                type="submit"
                                class="btn btn-danger btn-sm"
                                data-crm-confirm="Delete this task? This cannot be undone."
                                data-crm-confirm-title="Delete task"
                                data-crm-confirm-label="Delete"
                            >
                                <i class="fas fa-trash" aria-hidden="true"></i> Delete task
                            </button>
                        </form>
                    </div>
                </div>
            @endcan
        </div>
    </div>
</x-app-layout>
