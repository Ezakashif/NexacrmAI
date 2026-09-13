<x-app-layout>
    <x-slot name="header">
        <x-page-header
            title="Channels"
            subtitle="Connect WhatsApp, Meta, website forms, and other inbound sources."
            :breadcrumbs="[
                ['label' => 'Home', 'url' => route('dashboard')],
                ['label' => 'Channels'],
            ]"
        >
            <x-slot:actions>
                @can('create', App\Models\ChannelConnection::class)
                    <a href="{{ route('channels.create') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus" aria-hidden="true"></i> Connect channel
                    </a>
                @endcan
            </x-slot:actions>
        </x-page-header>
    </x-slot>

    <x-list-filters :reset-url="route('channels.index')">
        <div class="col-md-4 mb-2">
            <label for="search" class="small text-muted mb-1">Search</label>
            <input id="search" name="search" type="text" class="form-control form-control-sm"
                   placeholder="Name or account id..."
                   value="{{ $filters['search'] ?? '' }}">
        </div>
        <div class="col-md-3 mb-2">
            <label for="provider" class="small text-muted mb-1">Provider</label>
            <select id="provider" name="provider" class="custom-select custom-select-sm">
                <option value="">All</option>
                @foreach ($providers as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['provider'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 mb-2">
            <label for="status" class="small text-muted mb-1">Status</label>
            <select id="status" name="status" class="custom-select custom-select-sm">
                <option value="">All</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </x-list-filters>

    @php
        $hasFilters = collect($filters ?? [])->filter(fn ($value) => filled($value))->isNotEmpty();
        $canManage = auth()->user()->can('create', App\Models\ChannelConnection::class);
    @endphp

    <div class="card">
        @if ($channels->isEmpty())
            <div class="card-body">
                <x-empty-state
                    class="crm-empty--compact"
                    icon="fas fa-plug"
                    :title="$hasFilters ? 'No channels match your filters' : 'No channels connected'"
                    :description="$hasFilters
                        ? 'Try clearing filters or broadening your search.'
                        : 'Connect a channel to receive leads and conversations from Meta, WhatsApp, website forms, and more.'"
                    :action-url="$hasFilters ? route('channels.index') : ($canManage ? route('channels.create') : null)"
                    :action-label="$hasFilters ? 'Clear filters' : ($canManage ? 'Connect channel' : null)"
                />
            </div>
        @else
            <div class="card-body table-responsive p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Channel</th>
                            <th>Provider</th>
                            <th>Status</th>
                            <th>Health</th>
                            <th>Last sync</th>
                            <th>Last event</th>
                            <th>Errors</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($channels as $channel)
                            @php
                                $health = $connectionService->healthLabel($channel);
                                $statusClass = match ($channel->status->value) {
                                    'connected' => 'success',
                                    'pending' => 'info',
                                    'token_expiring' => 'warning',
                                    'error' => 'danger',
                                    default => 'secondary',
                                };
                                $healthClass = match ($health) {
                                    'Healthy' => 'success',
                                    'Warning' => 'warning',
                                    'Error' => 'danger',
                                    'Pending' => 'info',
                                    default => 'secondary',
                                };
                            @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('channels.show', $channel) }}">
                                        <strong>{{ $channel->name }}</strong>
                                    </a>
                                    @if ($channel->external_account_id)
                                        <small class="text-muted d-block">{{ $channel->external_account_id }}</small>
                                    @endif
                                </td>
                                <td>{{ $channel->provider->label() }}</td>
                                <td><span class="badge badge-{{ $statusClass }}">{{ $channel->status->label() }}</span></td>
                                <td><span class="badge badge-{{ $healthClass }}">{{ $health }}</span></td>
                                <td class="text-muted">{{ $channel->last_sync_at?->diffForHumans() ?? '—' }}</td>
                                <td class="text-muted">{{ $channel->last_event_at?->diffForHumans() ?? '—' }}</td>
                                <td>
                                    @if ($channel->error_count > 0)
                                        <span class="badge badge-danger">{{ $channel->error_count }}</span>
                                    @else
                                        <span class="text-muted">0</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <a href="{{ route('channels.show', $channel) }}" class="btn btn-sm btn-default">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($channels->hasPages())
                <div class="card-footer">{{ $channels->links() }}</div>
            @endif
        @endif
    </div>
</x-app-layout>
