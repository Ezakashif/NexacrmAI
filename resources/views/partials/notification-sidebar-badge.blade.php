@auth
    @if (auth()->user()->hasPermission('view.notifications'))
        @php($unreadNotificationCount = auth()->user()->unreadNotifications()->count())
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var notificationLink = document.querySelector('.nav-sidebar a[href$="/notifications"]');
                if (!notificationLink || {{ $unreadNotificationCount }} === 0) {
                    return;
                }

                var badge = document.createElement('span');
                badge.className = 'crm-sidebar-notification-badge';
                badge.textContent = {{ $unreadNotificationCount > 99 ? "'99+'" : $unreadNotificationCount }};
                badge.setAttribute('aria-label', '{{ $unreadNotificationCount }} unread notifications');
                notificationLink.appendChild(badge);
            });
        </script>
    @endif
@endauth
