<x-app-layout>
    <x-slot name="header">
        <x-page-header
            :title="$canViewAll ? 'Activity Log' : 'My Activity'"
            :subtitle="$canViewAll
                ? 'Audit trail across leads, customers, tasks, and users.'
                : 'Your recent actions in the CRM.'"
            :breadcrumbs="[
                ['label' => 'Home', 'url' => route('dashboard')],
                ['label' => $canViewAll ? 'Activity Log' : 'My Activity'],
            ]"
        />
    </x-slot>

    @php
        $hasFilters = filled(request('user_id')) || filled(request('action'));
    @endphp

    <x-list-filters :reset-url="route('activity-logs.index')">
        @if ($canViewAll)
            <div class="col-md-4 mb-2">
                <label for="user_id" class="small text-muted mb-1">User</label>
                <select name="user_id" id="user_id" class="form-control form-control-sm">
                    <option value="">All users</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected(request('user_id') == $user->id)>
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endif
        <div class="col-md-4 mb-2">
            <label for="action" class="small text-muted mb-1">Action</label>
            <select name="action" id="action" class="form-control form-control-sm">
                <option value="">All actions</option>
                @foreach ($actions as $value => $label)
                    <option value="{{ $value }}" @selected(request('action') === $value)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>
    </x-list-filters>

    <div class="card">
        @if ($logs->isEmpty())
            <div class="card-body">
                <x-empty-state
                    class="crm-empty--compact"
                    icon="fas fa-history"
                    :title="$hasFilters ? 'No activity matches your filters' : 'No activity recorded yet'"
                    :description="$hasFilters
                        ? 'Try clearing filters or choosing a different user or action.'
                        : ($canViewAll
                            ? 'Actions across the CRM will appear here as your team works.'
                            : 'Your CRM actions will appear here as you work.')"
                    :action-url="$hasFilters ? route('activity-logs.index') : null"
                    :action-label="$hasFilters ? 'Clear filters' : null"
                />
            </div>
        @else
            <div class="card-body table-responsive p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="width: 160px">Date</th>
                            <th style="width: 180px">User</th>
                            <th style="width: 160px">Action</th>
                            <th>Details</th>
                            <th style="width: 120px">IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($logs as $log)
                            @php $subjectUrl = $log->subjectShowUrl(); @endphp
                            <tr
                                @if ($subjectUrl)
                                    class="activity-log-row"
                                    tabindex="0"
                                    role="link"
                                    data-href="{{ $subjectUrl }}"
                                    aria-label="Open related record: {{ $log->description() }}"
                                @endif
                            >
                                <td>
                                    <span class="d-block text-dark">{{ $log->created_at->format('M d, Y') }}</span>
                                    <small class="text-muted">{{ $log->created_at->format('h:i A') }}</small>
                                </td>
                                <td>
                                    @if ($log->actor)
                                        <div class="d-flex align-items-center">
                                            <x-user-avatar :user="$log->actor" :size="28" class="mr-2" />
                                            <span>{{ $log->actor->name }}</span>
                                        </div>
                                    @else
                                        <span class="text-muted">System</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-light activity-log-action">{{ $log->actionLabel() }}</span>
                                </td>
                                <td>
                                    @if ($subjectUrl)
                                        <a href="{{ $subjectUrl }}" class="text-dark">
                                            {{ $log->description() }}
                                            <i class="fas fa-external-link-alt text-muted ml-1" style="font-size: 0.7rem;" aria-hidden="true"></i>
                                        </a>
                                    @else
                                        {{ $log->description() }}
                                    @endif
                                </td>
                                <td><small class="text-muted">{{ $log->ip_address ?? '—' }}</small></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($logs->hasPages())
                <div class="card-footer clearfix">
                    {{ $logs->links() }}
                </div>
            @endif
        @endif
    </div>

    @push('js')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('tr.activity-log-row[data-href]').forEach(function (row) {
                    row.addEventListener('click', function (event) {
                        if (event.target.closest('a')) {
                            return;
                        }
                        window.location = row.getAttribute('data-href');
                    });
                    row.addEventListener('keydown', function (event) {
                        if (event.key !== 'Enter' && event.key !== ' ') {
                            return;
                        }
                        if (event.target.closest('a')) {
                            return;
                        }
                        event.preventDefault();
                        window.location = row.getAttribute('data-href');
                    });
                });
            });
        </script>
    @endpush
</x-app-layout>
