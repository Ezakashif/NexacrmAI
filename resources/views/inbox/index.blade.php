<x-app-layout>
    <x-slot name="header">
        <x-page-header
            title="Inbox"
            subtitle="Conversations from WhatsApp and other connected channels."
            :breadcrumbs="[
                ['label' => 'Home', 'url' => route('dashboard')],
                ['label' => 'Inbox'],
            ]"
        />
    </x-slot>

    <x-list-filters :reset-url="route('inbox.index')">
        <div class="col-md-4 mb-2">
            <label for="search" class="small text-muted mb-1">Search</label>
            <input id="search" name="search" type="text" class="form-control form-control-sm"
                   placeholder="Name, phone, email..."
                   value="{{ $filters['search'] ?? '' }}">
        </div>
        <div class="col-md-2 mb-2">
            <label for="status" class="small text-muted mb-1">Status</label>
            <select id="status" name="status" class="custom-select custom-select-sm">
                <option value="">All</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 mb-2">
            <label for="provider" class="small text-muted mb-1">Channel</label>
            <select id="provider" name="provider" class="custom-select custom-select-sm">
                <option value="">All</option>
                @foreach ($providers as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['provider'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2 mb-2 d-flex align-items-end">
            <div class="custom-control custom-checkbox mb-1">
                <input type="checkbox" class="custom-control-input" id="unread" name="unread" value="1"
                       @checked(($filters['unread'] ?? '') === '1')>
                <label class="custom-control-label" for="unread">Unread only</label>
            </div>
        </div>
    </x-list-filters>

    @php
        $hasFilters = collect($filters ?? [])->filter(fn ($value) => filled($value))->isNotEmpty();
    @endphp

    <div class="card">
        @if ($conversations->isEmpty())
            <div class="card-body">
                <x-empty-state
                    class="crm-empty--compact"
                    icon="fas fa-inbox"
                    :title="$hasFilters ? 'No conversations match your filters' : 'Inbox is empty'"
                    :description="$hasFilters
                        ? 'Try clearing filters or broadening your search.'
                        : 'Inbound WhatsApp messages will appear here after a channel is connected.'"
                    :action-url="$hasFilters ? route('inbox.index') : null"
                    :action-label="$hasFilters ? 'Clear filters' : null"
                />
            </div>
        @else
            <div class="card-body table-responsive p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Contact</th>
                            <th>Channel</th>
                            <th>Status</th>
                            <th>Assignee</th>
                            <th>Last message</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($conversations as $conversation)
                            @php
                                $contactName = $conversation->contact?->display_name
                                    ?: $conversation->lead?->name
                                    ?: $conversation->external_thread_id
                                    ?: 'Unknown contact';
                                $statusClass = match ($conversation->status) {
                                    'open' => 'success',
                                    'pending' => 'warning',
                                    'closed' => 'secondary',
                                    default => 'light',
                                };
                            @endphp
                            <tr class="{{ $conversation->unread_count > 0 ? 'font-weight-bold' : '' }}">
                                <td>
                                    <a href="{{ route('inbox.show', $conversation) }}">{{ $contactName }}</a>
                                    @if ($conversation->unread_count > 0)
                                        <span class="badge badge-primary ml-1">{{ $conversation->unread_count }}</span>
                                    @endif
                                    @if ($conversation->contact?->phone || $conversation->lead?->phone)
                                        <small class="text-muted d-block">{{ $conversation->contact?->phone ?: $conversation->lead?->phone }}</small>
                                    @endif
                                </td>
                                <td>{{ $conversation->provider->label() }}</td>
                                <td><span class="badge badge-{{ $statusClass }}">{{ ucfirst($conversation->status) }}</span></td>
                                <td>{{ $conversation->assignee?->name ?: '—' }}</td>
                                <td class="text-muted">{{ $conversation->last_message_at?->diffForHumans() ?? '—' }}</td>
                                <td class="text-right">
                                    <a href="{{ route('inbox.show', $conversation) }}" class="btn btn-sm btn-default">Open</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($conversations->hasPages())
                <div class="card-footer">{{ $conversations->links() }}</div>
            @endif
        @endif
    </div>
</x-app-layout>
