<x-app-layout>
    @php
        $hasUnread = auth()->user()->unreadNotifications->isNotEmpty();
    @endphp

    <x-slot name="header">
        <x-page-header
            title="Notifications"
            subtitle="Follow-up reminders and system alerts."
            :breadcrumbs="[
                ['label' => 'Home', 'url' => route('dashboard')],
                ['label' => 'Notifications'],
            ]"
        >
            @if ($hasUnread)
                <x-slot:actions>
                    <form method="POST" action="{{ route('notifications.read-all') }}">
                        @csrf
                        <button type="submit" class="btn btn-default btn-sm">
                            <i class="fas fa-check-double" aria-hidden="true"></i> Mark all as read
                        </button>
                    </form>
                </x-slot:actions>
            @endif
        </x-page-header>
    </x-slot>

    <div class="card card-outline card-secondary">
        @if ($notifications->isEmpty())
            <div class="card-body">
                <x-empty-state
                    class="crm-empty--compact"
                    icon="fas fa-bell"
                    title="No notifications yet"
                    description="Follow-up reminders and alerts will appear here when they’re due."
                />
            </div>
        @else
            <div class="card-body p-0">
                <ul class="list-group list-group-flush crm-notification-list">
                    @foreach ($notifications as $notification)
                        @php
                            $data = $notification->data;
                            $isUnread = is_null($notification->read_at);
                            $title = $data['subject'] ?? 'Notification';
                            $message = $data['message'] ?? 'You have a new notification.';
                            $url = $data['url'] ?? null;
                            $actionLabel = match (true) {
                                ! empty($data['task_id']) => 'View task',
                                ! empty($data['customer_id']) => 'View customer',
                                ! empty($data['lead_id']) => 'View lead',
                                str_starts_with((string) $url, '/superadmin') => 'View alert',
                                default => 'View details',
                            };
                        @endphp
                        <li class="list-group-item crm-notification-item {{ $isUnread ? 'is-unread' : 'is-read' }}">
                            <div class="d-flex justify-content-between align-items-start flex-wrap" style="gap: 0.75rem;">
                                <div class="flex-grow-1 min-w-0">
                                    <div class="d-flex align-items-center flex-wrap mb-1" style="gap: 0.4rem;">
                                        @if ($isUnread)
                                            <span class="badge badge-warning">New</span>
                                        @endif
                                        @if (! empty($data['is_overdue']))
                                            <span class="badge badge-danger">Overdue</span>
                                        @endif
                                        <strong class="crm-notification-item__title">{{ $title }}</strong>
                                    </div>
                                    <p class="crm-notification-item__message mb-1">{{ $message }}</p>
                                    @if (! empty($data['follow_up_date']))
                                        <div class="small text-muted mb-1">
                                            <i class="fas fa-calendar-alt" aria-hidden="true"></i>
                                            Follow-up date: {{ \Carbon\Carbon::parse($data['follow_up_date'])->format('M j, Y') }}
                                        </div>
                                    @endif
                                    <div class="small text-muted">
                                        {{ $notification->created_at->diffForHumans() }}
                                    </div>
                                </div>
                                <div class="crm-notification-item__actions">
                                    @if (filled($url) && is_string($url))
                                        <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-primary btn-sm">
                                                <i class="fas fa-eye" aria-hidden="true"></i> {{ $actionLabel }}
                                            </button>
                                        </form>
                                    @elseif ($isUnread)
                                        <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-default btn-sm">
                                                Mark as read
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
            @if ($notifications->hasPages())
                <div class="card-footer clearfix">
                    {{ $notifications->links() }}
                </div>
            @endif
        @endif
    </div>
</x-app-layout>
