<x-app-layout>
    <x-slot name="header">
        <x-page-header
            title="Edit task"
            subtitle="Update task details and status."
            :breadcrumbs="[
                ['label' => 'Home', 'url' => route('dashboard')],
                ['label' => 'Tasks', 'url' => route('tasks.index')],
                ['label' => $task->title, 'url' => route('tasks.show', $task)],
                ['label' => 'Edit'],
            ]"
        >
            <x-slot:actions>
                <a href="{{ route('tasks.show', $task) }}" class="btn btn-default btn-sm">
                    <i class="fas fa-eye" aria-hidden="true"></i> View task
                </a>
            </x-slot:actions>
        </x-page-header>
    </x-slot>

    <div class="card card-outline card-primary">
        <form method="POST" action="{{ route('tasks.update', $task) }}">
            @csrf
            @method('PUT')
            <div class="card-body">
                <x-form-section title="Details">
                    <div class="form-group">
                        <x-form-label for="title" :required="true">Task title</x-form-label>
                        <input id="title" name="title" type="text" class="form-control @error('title') is-invalid @enderror"
                               value="{{ old('title', $task->title) }}" required>
                        @error('title')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-group mb-0">
                        <x-form-label for="description">Description</x-form-label>
                        <textarea id="description" name="description" class="form-control @error('description') is-invalid @enderror"
                                  rows="4">{{ old('description', $task->description) }}</textarea>
                        @error('description')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </x-form-section>

                <x-form-section title="Planning">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <x-form-label for="status">Status</x-form-label>
                                <select id="status" name="status" class="form-control @error('status') is-invalid @enderror">
                                    <option value="pending" @selected(old('status', $task->status) === 'pending')>Pending</option>
                                    <option value="in_progress" @selected(old('status', $task->status) === 'in_progress')>In Progress</option>
                                    <option value="completed" @selected(old('status', $task->status) === 'completed')>Completed</option>
                                    <option value="cancelled" @selected(old('status', $task->status) === 'cancelled')>Cancelled</option>
                                </select>
                                @error('status')<span class="invalid-feedback">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <x-form-label for="priority">Priority</x-form-label>
                                <select id="priority" name="priority" class="form-control @error('priority') is-invalid @enderror">
                                    <option value="low" @selected(old('priority', $task->priority) === 'low')>Low</option>
                                    <option value="medium" @selected(old('priority', $task->priority) === 'medium')>Medium</option>
                                    <option value="high" @selected(old('priority', $task->priority) === 'high')>High</option>
                                    <option value="urgent" @selected(old('priority', $task->priority) === 'urgent')>Urgent</option>
                                </select>
                                @error('priority')<span class="invalid-feedback">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <x-form-label for="due_date">Due date</x-form-label>
                                <input id="due_date" name="due_date" type="date"
                                       class="form-control @error('due_date') is-invalid @enderror"
                                       value="{{ old('due_date', $task->due_date ? \Carbon\Carbon::parse($task->due_date)->format('Y-m-d') : '') }}">
                                @error('due_date')<span class="invalid-feedback">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <x-form-label for="customer_id">Related customer</x-form-label>
                                <select id="customer_id" name="customer_id" class="form-control @error('customer_id') is-invalid @enderror">
                                    <option value="">— None —</option>
                                    @foreach ($customers as $customer)
                                        <option value="{{ $customer->id }}" @selected((string) old('customer_id', $task->customer_id) === (string) $customer->id)>
                                            {{ $customer->name }}
                                            @if ($customer->company_name)
                                                ({{ $customer->company_name }})
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('customer_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>

                    @can('assign', $task)
                        <div class="form-group mb-0">
                            <x-form-label for="assigned_to" :required="true">Assign to</x-form-label>
                            <select id="assigned_to" name="assigned_to" class="form-control @error('assigned_to') is-invalid @enderror" required>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}" @selected(old('assigned_to', $task->assigned_to) == $user->id)>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('assigned_to')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>
                    @else
                        <div class="form-group mb-0">
                            <x-form-label>Assigned to</x-form-label>
                            <input type="text" class="form-control" value="{{ $task->assignee?->name ?? '—' }}" disabled>
                        </div>
                    @endcan
                </x-form-section>
            </div>

            <div class="card-footer d-flex flex-wrap justify-content-between align-items-center">
                <div class="crm-form-actions mb-2 mb-md-0">
                    @can('update', $task)
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save" aria-hidden="true"></i> Update task
                        </button>
                    @endcan
                    <a href="{{ route('tasks.show', $task) }}" class="btn btn-default">Cancel</a>
                </div>
            </div>
        </form>
    </div>

    @can('delete', $task)
        <div class="card card-outline card-danger mt-3">
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
</x-app-layout>
