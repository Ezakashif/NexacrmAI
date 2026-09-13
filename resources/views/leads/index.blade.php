<x-app-layout>
    <x-slot name="header">
        <x-page-header
            title="Leads"
            subtitle="Track and move deals across your pipeline."
            :breadcrumbs="[
                ['label' => 'Home', 'url' => route('dashboard')],
                ['label' => 'Leads'],
            ]"
        >
            <x-slot:actions>
                @can('viewAny', App\Models\Lead::class)
                    <a href="{{ route('exports.leads', request()->query()) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-file-download" aria-hidden="true"></i> Export CSV
                    </a>
                @endcan
                @can('create', App\Models\Lead::class)
                    <a href="{{ route('imports.create', 'leads') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-file-upload" aria-hidden="true"></i> Import CSV
                    </a>
                    <a href="{{ route('leads.create') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus" aria-hidden="true"></i> Add Lead
                    </a>
                @endcan
            </x-slot:actions>
        </x-page-header>
    </x-slot>

    @if (! empty($boardTruncated))
        <div class="crm-banner crm-banner--warning crm-keep-alert mb-3" role="status">
            <div class="crm-banner__icon" aria-hidden="true"><i class="fas fa-filter"></i></div>
            <div class="crm-banner__body">
                Showing the first {{ \App\Models\Lead::BOARD_CARD_LIMIT }} leads. Narrow filters to see the rest.
            </div>
        </div>
    @endif

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

    <x-list-filters :reset-url="route('leads.index')">
        <div class="col-md-3 mb-2">
            <label for="search" class="small text-muted mb-1">Search</label>
            <input id="search" name="search" type="text" class="form-control form-control-sm"
                   placeholder="Name, email, phone, company..."
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
            <label for="source" class="small text-muted mb-1">Source</label>
            <select id="source" name="source" class="form-control form-control-sm">
                <option value="">All sources</option>
                @foreach (\App\Models\Lead::SOURCES as $source)
                    <option value="{{ $source }}" @selected(($filters['source'] ?? '') === $source)>
                        {{ ucfirst(str_replace('_', ' ', $source)) }}
                    </option>
                @endforeach
            </select>
        </div>
        @if (auth()->user()->canViewAllLeads())
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

    @php
        $statusColors = [
            'new' => 'card-primary',
            'contacted' => 'card-info',
            'qualified' => 'card-warning',
            'proposal_sent' => 'card-secondary',
            'won' => 'card-success',
            'lost' => 'card-danger',
        ];
        $hasFilters = collect($filters ?? [])->filter(fn ($value) => filled($value))->isNotEmpty();
        $canCreateLead = auth()->user()->can('create', App\Models\Lead::class);
        $canUpdateLeads = auth()->user()->hasPermission('update.leads');
    @endphp

    @if ($leads->isEmpty())
        <x-empty-state
            class="mb-3"
            icon="fas fa-funnel-dollar"
            :title="$hasFilters ? 'No leads match your filters' : 'No leads yet'"
            :description="$hasFilters
                ? 'Try clearing filters or broadening your search.'
                : 'Add your first lead to start the pipeline.'"
            :action-url="$hasFilters ? route('leads.index') : ($canCreateLead ? route('leads.create') : null)"
            :action-label="$hasFilters ? 'Clear filters' : ($canCreateLead ? 'Add lead' : null)"
        />
    @endif

    <nav class="crm-kanban-stages d-lg-none" aria-label="Pipeline stages">
        @foreach ($statuses as $statusKey => $statusTitle)
            @php $columnLeads = $leads->where('status', $statusKey); @endphp
            <button type="button" class="btn btn-sm btn-outline-secondary crm-kanban-stage" data-status="{{ $statusKey }}">
                <span class="crm-kanban-stage__label">{{ $statusTitle }}</span>
                <span class="badge badge-light column-count">{{ $columnLeads->count() }}</span>
            </button>
        @endforeach
    </nav>

    <div class="row crm-kanban" aria-label="Lead pipeline board">
        @foreach ($statuses as $statusKey => $statusTitle)
            @php $columnLeads = $leads->where('status', $statusKey); @endphp
            <div class="col-lg-4 col-md-6 mb-3">
                <div class="card {{ $statusColors[$statusKey] ?? 'card-secondary' }} card-outline crm-kanban-column">
                    <div class="card-header">
                        <h3 class="card-title">{{ $statusTitle }}</h3>
                        <div class="card-tools">
                            <span class="badge badge-light column-count" aria-label="{{ $columnLeads->count() }} leads">
                                {{ $columnLeads->count() }}
                            </span>
                        </div>
                    </div>
                    <div
                        class="card-body lead-column p-2"
                        data-status="{{ $statusKey }}"
                        @if ($statusKey === 'won') data-convert-only="1" @endif
                        aria-label="{{ $statusTitle }} column"
                    >
                        @forelse ($columnLeads as $lead)
                            <article
                                class="card card-sm mb-2 lead-card"
                                data-lead-id="{{ $lead->id }}"
                                style="cursor: {{ $canUpdateLeads ? 'grab' : 'default' }};"
                            >
                                <div class="card-body p-2">
                                    <h6 class="mb-1 lead-card__title">
                                        @can('view', $lead)
                                            <a href="{{ route('leads.show', $lead) }}" class="text-dark">{{ $lead->name }}</a>
                                        @else
                                            {{ $lead->name }}
                                        @endcan
                                    </h6>
                                    @if ($lead->company)
                                        <p class="text-muted small mb-1">
                                            <i class="fas fa-building" aria-hidden="true"></i> {{ $lead->company }}
                                        </p>
                                    @endif
                                    @if ($lead->estimated_value)
                                        <p class="text-muted small mb-1">
                                            <i class="fas fa-dollar-sign" aria-hidden="true"></i> {{ number_format($lead->estimated_value, 2) }}
                                        </p>
                                    @endif
                                    <div class="small text-muted mb-2">
                                        <i class="fas fa-user" aria-hidden="true"></i>
                                        {{ optional($lead->assignee)->name ?? 'Unassigned' }}
                                    </div>
                                    @if ($canUpdateLeads && $lead->status !== 'won')
                                        <label class="sr-only" for="lead-status-{{ $lead->id }}">Move {{ $lead->name }}</label>
                                        <select
                                            id="lead-status-{{ $lead->id }}"
                                            class="form-control form-control-sm crm-kanban-status"
                                            data-current="{{ $lead->status }}"
                                        >
                                            @foreach (\App\Models\Lead::manuallyAssignableStatuses($lead->status) as $optionKey => $optionLabel)
                                                <option value="{{ $optionKey }}" @selected($optionKey === $lead->status)>{{ $optionLabel }}</option>
                                            @endforeach
                                        </select>
                                    @endif
                                    @if (auth()->user()->can('view', $lead) || auth()->user()->can('update', $lead) || auth()->user()->can('convert', $lead))
                                        <div class="btn-group btn-group-xs lead-card__actions mt-2">
                                            @can('view', $lead)
                                                <a href="{{ route('leads.show', $lead) }}" class="btn btn-primary btn-xs">View</a>
                                            @endcan
                                            @can('update', $lead)
                                                <a href="{{ route('leads.edit', $lead) }}" class="btn btn-default btn-xs">Edit</a>
                                            @endcan
                                            @if ($lead->status !== 'won')
                                                @can('convert', $lead)
                                                    <form method="POST" action="{{ route('leads.convert', $lead) }}" class="d-inline">
                                                        @csrf
                                                        <button
                                                            type="submit"
                                                            class="btn btn-success btn-xs"
                                                            data-crm-confirm="Convert this lead to a customer? This marks the lead as won."
                                                            data-crm-confirm-title="Convert lead"
                                                            data-crm-confirm-label="Convert"
                                                            data-crm-confirm-class="btn-success"
                                                        >
                                                            Convert
                                                        </button>
                                                    </form>
                                                @endcan
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </article>
                        @empty
                            <div class="crm-kanban-empty" aria-hidden="true">
                                Drop leads here
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
        @if ($canUpdateLeads)
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    var board = document.querySelector('.crm-kanban');
                    var CrmKanban = window.CrmKanban || {};

                    function notify(type, message) {
                        if (CrmKanban.notify) {
                            CrmKanban.notify(type, message);
                            return;
                        }
                        window.alert(message);
                    }

                    function refreshColumnCounts() {
                        document.querySelectorAll('.lead-column').forEach(function (column) {
                            var card = column.closest('.card');
                            var badge = card ? card.querySelector('.column-count') : null;
                            if (badge) {
                                var count = column.querySelectorAll('.lead-card').length;
                                badge.textContent = count;
                                badge.setAttribute('aria-label', count + ' leads');
                            }

                            if (CrmKanban.refreshEmptyState) {
                                CrmKanban.refreshEmptyState(column, 'Drop leads here');
                            }

                            var stage = document.querySelector('.crm-kanban-stage[data-status="' + column.dataset.status + '"] .badge');
                            if (stage) {
                                stage.textContent = column.querySelectorAll('.lead-card').length;
                            }
                        });
                    }

                    if (CrmKanban.initStageNav) {
                        CrmKanban.initStageNav(board);
                    }

                    if (CrmKanban.bindStatusSelects) {
                        CrmKanban.bindStatusSelects({
                            board: board,
                            updateUrl: @json(route('leads.board.update')),
                            idAttr: 'data-lead-id',
                            payloadKey: 'lead_id',
                            cardSelector: '.lead-card',
                            columnSelector: '.lead-column',
                            emptyLabel: 'Drop leads here',
                            successMessage: 'Lead moved.',
                            refreshCounts: refreshColumnCounts,
                            beforeMove: function (card, nextStatus) {
                                var target = document.querySelector('.lead-column[data-status="' + nextStatus + '"]');
                                if (target && target.dataset.convertOnly === '1') {
                                    notify('warning', 'Mark a lead as won by converting it to a customer.');
                                    return false;
                                }
                                return true;
                            },
                        });
                    }

                    var touchOptions = CrmKanban.touchSortableOptions ? CrmKanban.touchSortableOptions() : {};

                    document.querySelectorAll('.lead-column').forEach(function (column) {
                        new Sortable(column, Object.assign({}, touchOptions, {
                            group: 'leads-kanban',
                            animation: 180,
                            draggable: '.lead-card',
                            ghostClass: 'lead-card--ghost',
                            chosenClass: 'lead-card--chosen',
                            dragClass: 'lead-card--dragging',
                            scroll: true,
                            bubbleScroll: true,
                            scrollSensitivity: 80,
                            scrollSpeed: 18,
                            emptyInsertThreshold: 48,
                            onStart: function (evt) {
                                document.body.classList.add('crm-kanban-dragging');
                                document.querySelectorAll('.lead-column').forEach(function (target) {
                                    target.classList.add('is-drop-target');
                                });
                                if (evt.item) {
                                    evt.item.style.cursor = 'grabbing';
                                }
                            },
                            onEnd: function (evt) {
                                document.body.classList.remove('crm-kanban-dragging');
                                document.querySelectorAll('.lead-column').forEach(function (target) {
                                    target.classList.remove('is-drop-target');
                                });
                                if (evt.item) {
                                    evt.item.style.cursor = 'grab';
                                }

                                var targetStatus = evt.to.dataset.status;
                                if (evt.to.dataset.convertOnly === '1' && evt.from !== evt.to) {
                                    if (typeof evt.from.insertBefore === 'function') {
                                        evt.from.insertBefore(evt.item, evt.from.children[evt.oldIndex] || null);
                                    }
                                    refreshColumnCounts();
                                    notify('warning', 'Mark a lead as won by converting it to a customer.');
                                    return;
                                }

                                if (evt.from === evt.to && evt.oldIndex === evt.newIndex) {
                                    return;
                                }

                                refreshColumnCounts();
                                evt.item.classList.add('is-saving');

                                var statusSelect = evt.item.querySelector('.crm-kanban-status');
                                if (statusSelect) {
                                    statusSelect.value = targetStatus;
                                    statusSelect.setAttribute('data-current', targetStatus);
                                }

                                fetch(@json(route('leads.board.update')), {
                                    method: "POST",
                                    headers: {
                                        "Content-Type": "application/json",
                                        "Accept": "application/json",
                                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
                                    },
                                    body: JSON.stringify({
                                        lead_id: evt.item.dataset.leadId,
                                        status: targetStatus,
                                        sort_order: evt.newIndex + 1
                                    })
                                }).then(function (res) {
                                    return res.json().then(function (data) {
                                        return { ok: res.ok, data: data };
                                    });
                                }).then(function (result) {
                                    evt.item.classList.remove('is-saving');
                                    if (! result.ok || ! result.data.success) {
                                        if (typeof evt.from.insertBefore === 'function') {
                                            evt.from.insertBefore(evt.item, evt.from.children[evt.oldIndex] || null);
                                        }
                                        if (statusSelect) {
                                            statusSelect.value = evt.from.dataset.status;
                                            statusSelect.setAttribute('data-current', evt.from.dataset.status);
                                        }
                                        refreshColumnCounts();
                                        notify('error', result.data.message || 'Unable to update lead.');
                                        return;
                                    }
                                    notify('success', 'Lead moved.');
                                }).catch(function () {
                                    evt.item.classList.remove('is-saving');
                                    if (typeof evt.from.insertBefore === 'function') {
                                        evt.from.insertBefore(evt.item, evt.from.children[evt.oldIndex] || null);
                                    }
                                    refreshColumnCounts();
                                    notify('error', 'Something went wrong.');
                                });
                            }
                        }));
                    });
                });
            </script>
        @else
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    document.querySelectorAll('.lead-card').forEach(function (card) {
                        card.style.cursor = 'default';
                    });
                    if (window.CrmKanban && window.CrmKanban.initStageNav) {
                        window.CrmKanban.initStageNav(document.querySelector('.crm-kanban'));
                    }
                });
            </script>
        @endif
    @endpush
</x-app-layout>
