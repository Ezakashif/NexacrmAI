<x-app-layout>
    <x-slot name="header">
        <x-page-header
            title="Tasks"
            subtitle="Organize work across pending, active, and done."
            :breadcrumbs="[
                ['label' => 'Home', 'url' => route('dashboard')],
                ['label' => 'Tasks'],
            ]"
        >
            <x-slot:actions>
                @can('viewAny', App\Models\Task::class)
                    <a href="{{ route('exports.tasks', request()->query()) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-file-download" aria-hidden="true"></i> Export CSV
                    </a>
                @endcan
                @can('create', App\Models\Task::class)
                    <a href="{{ route('tasks.create') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus" aria-hidden="true"></i> Add Task
                    </a>
                @endcan
            </x-slot:actions>
        </x-page-header>
    </x-slot>

    @if (! empty($boardTruncated))
        <div class="crm-banner crm-banner--warning crm-keep-alert mb-3" role="status">
            <div class="crm-banner__icon" aria-hidden="true"><i class="fas fa-filter"></i></div>
            <div class="crm-banner__body">
                Showing the first {{ \App\Models\Task::BOARD_CARD_LIMIT }} tasks. Narrow filters to see the rest.
            </div>
        </div>
    @endif

    @php
        $statusColors = [
            'pending' => 'card-warning',
            'in_progress' => 'card-info',
            'completed' => 'card-success',
            'cancelled' => 'card-secondary',
        ];
        $canDragTasks = auth()->user()->hasPermission('update.tasks')
            || auth()->user()->hasPermission('change_status.tasks');
        $hasFilters = collect($filters ?? [])->filter(fn ($value) => filled($value))->isNotEmpty();
        $canCreateTask = auth()->user()->can('create', App\Models\Task::class);
    @endphp

    <x-list-filters :reset-url="route('tasks.index')">
        <div class="col-md-3 mb-2">
            <label for="search" class="small text-muted mb-1">Search</label>
            <input id="search" name="search" type="text" class="form-control form-control-sm"
                   placeholder="Title or description..."
                   value="{{ $filters['search'] ?? '' }}">
        </div>
        <div class="col-md-2 mb-2">
            <label for="status" class="small text-muted mb-1">Status</label>
            <select id="status" name="status" class="form-control form-control-sm">
                <option value="">All statuses</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2 mb-2">
            <label for="priority" class="small text-muted mb-1">Priority</label>
            <select id="priority" name="priority" class="form-control form-control-sm">
                <option value="">All priorities</option>
                @foreach ($priorities as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['priority'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        @if (auth()->user()->canViewAllTasks())
            <div class="col-md-3 mb-2">
                <label for="assigned_to" class="small text-muted mb-1">Assigned to</label>
                <select id="assigned_to" name="assigned_to" class="form-control form-control-sm">
                    <option value="">Anyone</option>
                    <option value="unassigned" @selected(($filters['assigned_to'] ?? '') === 'unassigned')>Unassigned</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected(($filters['assigned_to'] ?? '') == $user->id)>
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endif
    </x-list-filters>

    @if ($tasks->isEmpty())
        <x-empty-state
            class="mb-3"
            icon="fas fa-tasks"
            :title="$hasFilters ? 'No tasks match your filters' : 'No tasks yet'"
            :description="$hasFilters
                ? 'Try clearing filters or broadening your search.'
                : 'Create a task when something needs follow-through.'"
            :action-url="$hasFilters ? route('tasks.index') : ($canCreateTask ? route('tasks.create') : null)"
            :action-label="$hasFilters ? 'Clear filters' : ($canCreateTask ? 'Add task' : null)"
        />
    @endif

    <nav class="crm-kanban-stages d-lg-none" aria-label="Task stages">
        @foreach ($statuses as $statusKey => $statusTitle)
            @php $columnTasks = $tasks->where('status', $statusKey); @endphp
            <button type="button" class="btn btn-sm btn-outline-secondary crm-kanban-stage" data-status="{{ $statusKey }}">
                <span class="crm-kanban-stage__label">{{ $statusTitle }}</span>
                <span class="badge badge-light column-count">{{ $columnTasks->count() }}</span>
            </button>
        @endforeach
    </nav>

    <div class="row crm-kanban" aria-label="Task board">
        @foreach ($statuses as $statusKey => $statusTitle)
            @php $columnTasks = $tasks->where('status', $statusKey); @endphp
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card {{ $statusColors[$statusKey] ?? 'card-secondary' }} card-outline crm-kanban-column">
                    <div class="card-header">
                        <h3 class="card-title">{{ $statusTitle }}</h3>
                        <div class="card-tools">
                            <span class="badge badge-light column-count" aria-label="{{ $columnTasks->count() }} tasks">
                                {{ $columnTasks->count() }}
                            </span>
                        </div>
                    </div>
                    <div class="card-body task-column p-2" data-status="{{ $statusKey }}" aria-label="{{ $statusTitle }} column">
                        @forelse ($columnTasks as $task)
                            @php($canChangeStatus = auth()->user()->can('changeStatus', $task))
                            <article
                                class="card card-sm mb-2 task-card"
                                data-task-id="{{ $task->id }}"
                                data-draggable="{{ $canChangeStatus ? '1' : '0' }}"
                                style="cursor: {{ $canChangeStatus ? 'grab' : 'default' }};"
                            >
                                <div class="card-body p-2">
                                    <h6 class="mb-1 task-card__title">
                                        @can('view', $task)
                                            <a href="{{ route('tasks.show', $task) }}" class="text-dark">{{ $task->title }}</a>
                                        @else
                                            {{ $task->title }}
                                        @endcan
                                    </h6>
                                    @if ($task->description)
                                        <p class="text-muted small mb-2">{{ Str::limit($task->description, 80) }}</p>
                                    @endif
                                    <div class="d-flex justify-content-between small text-muted mb-2">
                                        <span>
                                            <i class="fas fa-user" aria-hidden="true"></i>
                                            {{ optional($task->assignee)->name ?? 'Unassigned' }}
                                        </span>
                                        <span>
                                            <i class="fas fa-calendar" aria-hidden="true"></i>
                                            {{ $task->due_date ? \Carbon\Carbon::parse($task->due_date)->format('d M') : '—' }}
                                        </span>
                                    </div>
                                    @if ($canChangeStatus)
                                        <label class="sr-only" for="task-status-{{ $task->id }}">Move {{ $task->title }}</label>
                                        <select
                                            id="task-status-{{ $task->id }}"
                                            class="form-control form-control-sm crm-kanban-status"
                                            data-current="{{ $task->status }}"
                                        >
                                            @foreach ($statuses as $optionKey => $optionLabel)
                                                <option value="{{ $optionKey }}" @selected($optionKey === $task->status)>{{ $optionLabel }}</option>
                                            @endforeach
                                        </select>
                                    @endif
                                    <div class="task-card__actions mt-2">
                                        @can('view', $task)
                                            <a href="{{ route('tasks.show', $task) }}"
                                               class="btn btn-xs btn-primary"
                                               title="View task"
                                               aria-label="View {{ $task->title }}">
                                                <i class="fas fa-eye" aria-hidden="true"></i>
                                            </a>
                                        @endcan
                                        @can('update', $task)
                                            <a href="{{ route('tasks.edit', $task) }}"
                                               class="btn btn-xs btn-default"
                                               title="Edit task"
                                               aria-label="Edit {{ $task->title }}">
                                                <i class="fas fa-edit" aria-hidden="true"></i>
                                            </a>
                                        @endcan
                                        @can('delete', $task)
                                            <form method="POST" action="{{ route('tasks.destroy', $task) }}" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    type="submit"
                                                    class="btn btn-xs btn-danger"
                                                    title="Delete task"
                                                    aria-label="Delete {{ $task->title }}"
                                                    data-crm-confirm="Delete this task? This cannot be undone."
                                                    data-crm-confirm-title="Delete task"
                                                    data-crm-confirm-label="Delete"
                                                >
                                                    <i class="fas fa-trash" aria-hidden="true"></i>
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </div>
                            </article>
                        @empty
                            <div class="crm-kanban-empty" aria-hidden="true">
                                Drop tasks here
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @push('js')
        <script src="{{ asset('js/crm-kanban.js') }}"></script>
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
        @if ($canDragTasks)
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    var board = document.querySelector('.crm-kanban');
                    var CrmKanban = window.CrmKanban || {};
                    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
                    var csrfToken = csrfMeta ? csrfMeta.content : @json(csrf_token());

                    function notify(type, message) {
                        if (CrmKanban.notify) {
                            CrmKanban.notify(type, message);
                            return;
                        }
                        window.alert(message);
                    }

                    function refreshColumnCounts() {
                        document.querySelectorAll('.task-column').forEach(function (column) {
                            var card = column.closest('.card');
                            var badge = card ? card.querySelector('.column-count') : null;
                            if (badge) {
                                var count = column.querySelectorAll('.task-card').length;
                                badge.textContent = count;
                                badge.setAttribute('aria-label', count + ' tasks');
                            }

                            if (CrmKanban.refreshEmptyState) {
                                CrmKanban.refreshEmptyState(column, 'Drop tasks here');
                            }

                            var stage = document.querySelector('.crm-kanban-stage[data-status="' + column.dataset.status + '"] .badge');
                            if (stage) {
                                stage.textContent = column.querySelectorAll('.task-card').length;
                            }
                        });
                    }

                    if (CrmKanban.initStageNav) {
                        CrmKanban.initStageNav(board);
                    }

                    if (CrmKanban.bindStatusSelects) {
                        CrmKanban.bindStatusSelects({
                            board: board,
                            updateUrl: @json(route('tasks.board.update')),
                            idAttr: 'data-task-id',
                            payloadKey: 'task_id',
                            cardSelector: '.task-card',
                            columnSelector: '.task-column',
                            emptyLabel: 'Drop tasks here',
                            successMessage: 'Task moved.',
                            refreshCounts: refreshColumnCounts,
                        });
                    }

                    var touchOptions = CrmKanban.touchSortableOptions ? CrmKanban.touchSortableOptions() : {};

                    document.querySelectorAll('.task-column').forEach(function (column) {
                        new Sortable(column, Object.assign({}, touchOptions, {
                            group: 'tasks-kanban',
                            animation: 180,
                            draggable: '.task-card[data-draggable="1"]',
                            ghostClass: 'task-card--ghost',
                            chosenClass: 'task-card--chosen',
                            dragClass: 'task-card--dragging',
                            scroll: true,
                            bubbleScroll: true,
                            scrollSensitivity: 80,
                            scrollSpeed: 18,
                            emptyInsertThreshold: 48,
                            onStart: function (evt) {
                                document.body.classList.add('crm-kanban-dragging');
                                document.querySelectorAll('.task-column').forEach(function (target) {
                                    target.classList.add('is-drop-target');
                                });
                                if (evt.item) {
                                    evt.item.style.cursor = 'grabbing';
                                }
                            },
                            onEnd: function (evt) {
                                document.body.classList.remove('crm-kanban-dragging');
                                document.querySelectorAll('.task-column').forEach(function (target) {
                                    target.classList.remove('is-drop-target');
                                });
                                if (evt.item && evt.item.dataset.draggable === '1') {
                                    evt.item.style.cursor = 'grab';
                                }

                                if (evt.from === evt.to && evt.oldIndex === evt.newIndex) {
                                    return;
                                }

                                refreshColumnCounts();
                                evt.item.classList.add('is-saving');

                                var statusSelect = evt.item.querySelector('.crm-kanban-status');
                                if (statusSelect) {
                                    statusSelect.value = evt.to.dataset.status;
                                    statusSelect.setAttribute('data-current', evt.to.dataset.status);
                                }

                                fetch(@json(route('tasks.board.update')), {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': csrfToken,
                                        'X-Requested-With': 'XMLHttpRequest'
                                    },
                                    body: JSON.stringify({
                                        task_id: evt.item.dataset.taskId,
                                        status: evt.to.dataset.status,
                                        sort_order: evt.newIndex + 1
                                    })
                                }).then(function (res) {
                                    return res.json().then(function (data) {
                                        return { ok: res.ok, data: data };
                                    }).catch(function () {
                                        return { ok: false, data: null };
                                    });
                                }).then(function (result) {
                                    evt.item.classList.remove('is-saving');
                                    if (! result.ok || ! result.data || ! result.data.success) {
                                        evt.from.insertBefore(evt.item, evt.from.children[evt.oldIndex] || null);
                                        if (statusSelect) {
                                            statusSelect.value = evt.from.dataset.status;
                                            statusSelect.setAttribute('data-current', evt.from.dataset.status);
                                        }
                                        refreshColumnCounts();
                                        notify('error', (result.data && result.data.message) || 'Unable to update task status.');
                                        return;
                                    }
                                    refreshColumnCounts();
                                    notify('success', 'Task moved.');
                                }).catch(function () {
                                    evt.item.classList.remove('is-saving');
                                    evt.from.insertBefore(evt.item, evt.from.children[evt.oldIndex] || null);
                                    refreshColumnCounts();
                                    notify('error', 'Something went wrong while updating the task.');
                                });
                            }
                        }));
                    });
                });
            </script>
        @else
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    if (window.CrmKanban && window.CrmKanban.initStageNav) {
                        window.CrmKanban.initStageNav(document.querySelector('.crm-kanban'));
                    }
                });
            </script>
        @endif
    @endpush
</x-app-layout>
