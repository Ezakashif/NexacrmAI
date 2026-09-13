<x-app-layout>
    <x-slot name="header">
        <x-page-header
            title="Create task"
            subtitle="Assign work with a due date and priority."
            :breadcrumbs="[
                ['label' => 'Home', 'url' => route('dashboard')],
                ['label' => 'Tasks', 'url' => route('tasks.index')],
                ['label' => 'Create'],
            ]"
        />
    </x-slot>

    <div class="card card-outline card-primary">
        <form method="POST" action="{{ route('tasks.store') }}">
            @csrf
            <div class="card-body">
                <x-form-section title="Details" description="What needs to get done?">
                    <div class="form-group">
                        <x-form-label for="title" :required="true">Task title</x-form-label>
                        <input id="title" name="title" type="text" class="form-control @error('title') is-invalid @enderror"
                               value="{{ old('title') }}" required>
                        @error('title')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-group mb-0">
                        <x-form-label for="description">Description</x-form-label>
                        <textarea id="description" name="description" class="form-control @error('description') is-invalid @enderror"
                                  rows="4">{{ old('description') }}</textarea>
                        @error('description')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </x-form-section>

                <x-form-section title="Planning" description="Priority, due date, and links.">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <x-form-label for="priority">Priority</x-form-label>
                                <select id="priority" name="priority" class="form-control @error('priority') is-invalid @enderror">
                                    @foreach (\App\Models\Task::PRIORITIES as $value => $label)
                                        <option value="{{ $value }}" @selected(old('priority', 'medium') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('priority')<span class="invalid-feedback">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <x-form-label for="due_date">Due date</x-form-label>
                                <input id="due_date" name="due_date" type="date"
                                       class="form-control @error('due_date') is-invalid @enderror"
                                       value="{{ old('due_date') }}">
                                @error('due_date')<span class="invalid-feedback">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <x-form-label for="customer_id">Related customer</x-form-label>
                        <select id="customer_id" name="customer_id" class="form-control @error('customer_id') is-invalid @enderror">
                            <option value="">— None —</option>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}" @selected((string) old('customer_id', request('customer_id')) === (string) $customer->id)>
                                    {{ $customer->name }}
                                    @if ($customer->company_name)
                                        ({{ $customer->company_name }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        @error('customer_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>

                    @if (auth()->user()->canAssignTasks())
                        <div class="form-group mb-0">
                            <x-form-label for="assigned_to" :required="true">Assign to</x-form-label>
                            <select id="assigned_to" name="assigned_to" class="form-control @error('assigned_to') is-invalid @enderror" required>
                                <option value="">— Select user —</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}" @selected(old('assigned_to') == $user->id)>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('assigned_to')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>
                    @else
                        <div class="form-group mb-0">
                            <x-form-label>Assign to</x-form-label>
                            <input type="text" class="form-control" value="{{ auth()->user()->name }} (you)" disabled>
                            <small class="form-text text-muted">Tasks you create are assigned to you.</small>
                        </div>
                    @endif
                </x-form-section>
            </div>

            <div class="card-footer">
                <div class="crm-form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save" aria-hidden="true"></i> Save task
                    </button>
                    <a href="{{ route('tasks.index') }}" class="btn btn-default">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
