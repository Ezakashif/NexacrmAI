<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Super Admin') — {{ config('app.name') }}</title>
    <link rel="icon" href="{{ app(\App\Services\SuperAdmin\PlatformSettingsService::class)->faviconUrl() ?: asset(config('marketing.assets.favicon', 'branding/nexacrm-mark.svg')) }}" type="image/svg+xml">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/sa-app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/password-field.css') }}">
    @stack('styles')
</head>
<body class="sa-app">
@php
    $platformSettings = app(\App\Services\SuperAdmin\PlatformSettingsService::class);
    $platformLogo = $platformSettings->logoUrl();
    $flashes = collect([
        'success' => session('success'),
        'error' => session('error') ?? session('danger'),
        'warning' => session('warning'),
        'info' => session('info'),
    ])->filter(fn ($message) => filled($message) && is_string($message));
    $newContactInquiryCount = \Illuminate\Support\Facades\Schema::hasTable('contact_inquiries')
        ? \App\Models\ContactInquiry::query()->new()->count()
        : 0;
    $newDemoVisitCount = \Illuminate\Support\Facades\Schema::hasTable('demo_visits')
        ? \App\Models\DemoVisit::query()->new()->count()
        : 0;
    $unreadNotificationCount = auth()->user()?->unreadNotifications()->count() ?? 0;
    $recentUnreadNotifications = auth()->user()
        ? auth()->user()->unreadNotifications()->latest()->limit(5)->get()
        : collect();
@endphp
<div class="sa-shell">
    <aside class="sa-nav" aria-label="Super Admin navigation">
        <div class="sa-brand">
            @if ($platformLogo)
                <img src="{{ $platformSettings->logoUrl('light') ?: $platformLogo }}" alt="{{ $platformSettings->platformName() }}" class="sa-brand-logo">
            @else
                <div class="sa-brand-text">{{ $platformSettings->platformName() }} <span>Platform</span></div>
            @endif
        </div>
        <a href="{{ route('superadmin.dashboard') }}" class="sa-nav-link {{ request()->routeIs('superadmin.dashboard') ? 'active' : '' }}">
            <i class="fas fa-tachometer-alt" aria-hidden="true"></i> Dashboard
        </a>
        <a href="{{ route('superadmin.companies.index') }}" class="sa-nav-link {{ request()->routeIs('superadmin.companies.*') ? 'active' : '' }}">
            <i class="fas fa-building" aria-hidden="true"></i> Companies
        </a>
        <a href="{{ route('superadmin.plans.index') }}" class="sa-nav-link {{ request()->routeIs('superadmin.plans.*') ? 'active' : '' }}">
            <i class="fas fa-layer-group" aria-hidden="true"></i> Subscription Plans
        </a>
        <a href="{{ route('superadmin.super-admins.index') }}" class="sa-nav-link {{ request()->routeIs('superadmin.super-admins.*') ? 'active' : '' }}">
            <i class="fas fa-user-shield" aria-hidden="true"></i> Super Admins
        </a>
        <a href="{{ route('superadmin.email-templates.index') }}" class="sa-nav-link {{ request()->routeIs('superadmin.email-templates.*') ? 'active' : '' }}">
            <i class="fas fa-envelope-open-text" aria-hidden="true"></i> Email Templates
        </a>
        <a href="{{ route('superadmin.contact-inquiries.index') }}" class="sa-nav-link {{ request()->routeIs('superadmin.contact-inquiries.*') ? 'active' : '' }}">
            <i class="fas fa-inbox" aria-hidden="true"></i>
            <span>Contact inquiries</span>
            @if ($newContactInquiryCount > 0)
                <span class="sa-nav-badge">{{ $newContactInquiryCount > 99 ? '99+' : $newContactInquiryCount }}</span>
            @endif
        </a>
        <a href="{{ route('superadmin.demo-visits.index') }}" class="sa-nav-link {{ request()->routeIs('superadmin.demo-visits.*') ? 'active' : '' }}">
            <i class="fas fa-user-clock" aria-hidden="true"></i>
            <span>Demo visits</span>
            @if ($newDemoVisitCount > 0)
                <span class="sa-nav-badge">{{ $newDemoVisitCount > 99 ? '99+' : $newDemoVisitCount }}</span>
            @endif
        </a>
        <a href="{{ route('superadmin.notifications.index') }}" class="sa-nav-link {{ request()->routeIs('superadmin.notifications.*') ? 'active' : '' }}">
            <i class="fas fa-bell" aria-hidden="true"></i>
            <span>Notifications</span>
            @if ($unreadNotificationCount > 0)
                <span class="sa-nav-badge">{{ $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount }}</span>
            @endif
        </a>
        <a href="{{ route('superadmin.settings.edit') }}" class="sa-nav-link {{ request()->routeIs('superadmin.settings.*') ? 'active' : '' }}">
            <i class="fas fa-cog" aria-hidden="true"></i> Settings
        </a>
        <a href="{{ route('docs.index') }}" class="sa-nav-link {{ request()->routeIs('docs.*') ? 'active' : '' }}">
            <i class="fas fa-book" aria-hidden="true"></i> Documentation
        </a>
        <a href="{{ route('superadmin.search.index') }}" class="sa-nav-link {{ request()->routeIs('superadmin.search.*') ? 'active' : '' }}">
            <i class="fas fa-search" aria-hidden="true"></i> Search
        </a>
        <hr class="sa-nav-divider">
        <form method="POST" action="{{ route('logout') }}" data-sa-no-loading="1">
            @csrf
            <button type="submit" class="sa-nav-logout">
                <i class="fas fa-sign-out-alt" aria-hidden="true"></i> Log out
            </button>
        </form>
    </aside>

    <main class="sa-main">
        <div class="sa-top">
            <div>
                <h1 class="h3 mb-0 text-white">@yield('heading', 'Super Admin')</h1>
                @hasSection('subheading')
                    <div class="sa-muted mt-1">@yield('subheading')</div>
                @endif
            </div>
            <div class="d-flex align-items-center flex-wrap" style="gap: 0.75rem;">
                <form action="{{ route('superadmin.search.index') }}" method="GET" class="sa-search" id="sa-global-search" data-sa-no-loading="1">
                    <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Search companies, users..." autocomplete="off" id="sa-search-input" aria-label="Search companies and users">
                    <div class="sa-search-results" id="sa-search-results" role="listbox"></div>
                </form>

                <div class="dropdown sa-notification-dropdown">
                    <button
                        class="sa-notification-bell btn btn-sm btn-outline-light dropdown-toggle"
                        type="button"
                        id="sa-notification-toggle"
                        data-toggle="dropdown"
                        aria-haspopup="true"
                        aria-expanded="false"
                        aria-label="Notifications"
                    >
                        <i class="fas fa-bell" aria-hidden="true"></i>
                        @if ($unreadNotificationCount > 0)
                            <span class="sa-notification-bell__count">{{ $unreadNotificationCount > 9 ? '9+' : $unreadNotificationCount }}</span>
                        @endif
                    </button>
                    <div class="dropdown-menu dropdown-menu-right sa-notification-menu" aria-labelledby="sa-notification-toggle">
                        <div class="sa-notification-menu__header">
                            <strong>Notifications</strong>
                            @if ($unreadNotificationCount > 0)
                                <form method="POST" action="{{ route('superadmin.notifications.read-all') }}" class="mb-0">
                                    @csrf
                                    <button type="submit" class="btn btn-link btn-sm p-0">Mark all read</button>
                                </form>
                            @endif
                        </div>
                        @forelse ($recentUnreadNotifications as $notification)
                            @php
                                $data = $notification->data;
                                $title = $data['subject'] ?? 'Notification';
                                $message = $data['message'] ?? '';
                            @endphp
                            <form method="POST" action="{{ route('superadmin.notifications.read', $notification->id) }}" class="mb-0">
                                @csrf
                                <button type="submit" class="dropdown-item sa-notification-menu__item">
                                    <span class="sa-notification-menu__title">{{ $title }}</span>
                                    <span class="sa-notification-menu__message">{{ \Illuminate\Support\Str::limit($message, 72) }}</span>
                                    <span class="sa-notification-menu__time">{{ $notification->created_at->diffForHumans() }}</span>
                                </button>
                            </form>
                        @empty
                            <div class="sa-notification-menu__empty">No unread notifications</div>
                        @endforelse
                        <div class="sa-notification-menu__footer">
                            <a href="{{ route('superadmin.notifications.index') }}">View all notifications</a>
                        </div>
                    </div>
                </div>

                <div class="sa-muted small">{{ auth()->user()->name }}</div>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger sa-keep-alert">
                <ul class="mb-0 pl-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>
</div>

<div id="sa-toast-stack" class="sa-toast-stack" aria-live="polite" aria-relevant="additions"></div>

<div
    id="sa-confirm-backdrop"
    class="sa-confirm-backdrop"
    hidden
    role="dialog"
    aria-modal="true"
    aria-labelledby="sa-confirm-title"
    aria-describedby="sa-confirm-message"
>
    <div class="sa-confirm">
        <h2 id="sa-confirm-title" class="sa-confirm__title">Are you sure?</h2>
        <p id="sa-confirm-message" class="sa-confirm__message">This action cannot be undone.</p>
        <div class="sa-confirm__actions">
            <button type="button" class="btn btn-outline-light" data-sa-confirm-cancel>Cancel</button>
            <button type="button" class="btn btn-danger" data-sa-confirm-ok>Confirm</button>
        </div>
    </div>
</div>

<script type="application/json" id="sa-flash-data">@json($flashes->map(fn ($message, $type) => ['type' => $type === 'error' ? 'error' : $type, 'message' => $message])->values())</script>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.4/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/sa-ui.js') }}"></script>
<script src="{{ asset('js/password-toggle.js') }}"></script>
<script>
(function () {
    const input = document.getElementById('sa-search-input');
    const results = document.getElementById('sa-search-results');
    if (!input || !results) return;

    let timer = null;
    const suggestUrl = @json(route('superadmin.search.suggest'));

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function escapeAttr(value) {
        return escapeHtml(value).replace(/`/g, '&#096;');
    }

    input.addEventListener('input', function () {
        clearTimeout(timer);
        const q = input.value.trim();
        if (q.length < 2) {
            results.classList.remove('open');
            results.innerHTML = '';
            return;
        }
        timer = setTimeout(async function () {
            try {
                const response = await fetch(suggestUrl + '?q=' + encodeURIComponent(q), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await response.json();
                let html = '';
                if (data.companies && data.companies.length) {
                    html += '<div class="px-3 pt-2 sa-muted small">Companies</div>';
                    data.companies.forEach(function (item) {
                        html += '<a href="' + escapeAttr(item.url) + '"><strong>' + escapeHtml(item.name) + '</strong><div class="sa-muted small">' + escapeHtml(item.slug || '') + '</div></a>';
                    });
                }
                if (data.users && data.users.length) {
                    html += '<div class="px-3 pt-2 sa-muted small">Users</div>';
                    data.users.forEach(function (item) {
                        html += '<a href="' + escapeAttr(item.url) + '"><strong>' + escapeHtml(item.name) + '</strong><div class="sa-muted small">' + escapeHtml(item.email) + (item.company ? ' · ' + escapeHtml(item.company) : '') + '</div></a>';
                    });
                }
                if (!html) {
                    html = '<div class="px-3 py-2 sa-muted">No matches</div>';
                }
                results.innerHTML = html;
                results.classList.add('open');
            } catch (e) {
                results.classList.remove('open');
            }
        }, 220);
    });

    document.addEventListener('click', function (event) {
        if (!event.target.closest('#sa-global-search')) {
            results.classList.remove('open');
        }
    });
})();
</script>
@stack('scripts')
</body>
</html>
