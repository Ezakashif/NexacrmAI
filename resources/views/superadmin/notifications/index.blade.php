@extends('superadmin.layout')

@section('title', 'Notifications')
@section('heading', 'Notifications')
@section('subheading', 'Platform alerts and contact inquiry updates')

@section('content')
@php
    $hasUnread = auth()->user()->unreadNotifications->isNotEmpty();
@endphp

<div class="sa-toolbar">
    <div class="sa-toolbar__meta">
        <span class="sa-toolbar__count">{{ $notifications->total() }} {{ \Illuminate\Support\Str::plural('notification', $notifications->total()) }}</span>
    </div>
    <div class="sa-toolbar__actions">
        @if ($hasUnread)
            <form method="POST" action="{{ route('superadmin.notifications.read-all') }}">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-light">
                    <i class="fas fa-check-double" aria-hidden="true"></i> Mark all as read
                </button>
            </form>
        @endif
    </div>
</div>

<div class="sa-card">
    @if ($notifications->isEmpty())
        <div class="sa-empty py-5">
            <div class="sa-empty__icon" aria-hidden="true"><i class="fas fa-bell"></i></div>
            <h3 class="sa-empty__title">No notifications yet</h3>
            <p class="sa-empty__text">Demo requests, contact inquiries, and platform alerts will appear here.</p>
        </div>
    @else
        <div class="list-group list-group-flush sa-notification-list">
            @foreach ($notifications as $notification)
                @php
                    $data = $notification->data;
                    $isUnread = is_null($notification->read_at);
                    $title = $data['subject'] ?? 'Notification';
                    $message = $data['message'] ?? 'You have a new notification.';
                    $url = $data['url'] ?? null;
                @endphp
                <div class="list-group-item sa-notification-item {{ $isUnread ? 'is-unread' : '' }}">
                    <div class="d-flex justify-content-between align-items-start flex-wrap" style="gap: 0.75rem;">
                        <div class="flex-grow-1 min-w-0">
                            <div class="d-flex align-items-center flex-wrap mb-1" style="gap: 0.4rem;">
                                @if ($isUnread)
                                    <span class="badge badge-warning">New</span>
                                @endif
                                <strong class="text-white">{{ $title }}</strong>
                            </div>
                            <p class="sa-muted mb-1">{{ $message }}</p>
                            <div class="small sa-muted">{{ $notification->created_at->diffForHumans() }}</div>
                        </div>
                        <div>
                            @if (filled($url) && is_string($url))
                                <form method="POST" action="{{ route('superadmin.notifications.read', $notification->id) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-info">Open</button>
                                </form>
                            @elseif ($isUnread)
                                <form method="POST" action="{{ route('superadmin.notifications.read', $notification->id) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-light">Mark read</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-3">
            {{ $notifications->links() }}
        </div>
    @endif
</div>
@endsection
