<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0284c7">
    <meta name="format-detection" content="telephone=no">

    <x-marketing.seo
        :title="$title ?? null"
        :description="$description ?? null"
        :image="$ogImage ?? null"
    />
    <x-marketing.json-ld type="organization" />
    <x-marketing.json-ld type="website" />

    @php($platformFavicon = app(\App\Services\SuperAdmin\PlatformSettingsService::class)->faviconUrl())
    <link rel="icon" href="{{ $platformFavicon ?: asset('branding/algos-logo.svg') }}" @if (! $platformFavicon) type="image/svg+xml" @endif>
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link rel="preload" as="style" href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
    <noscript>
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet">
    </noscript>

    @vite(['resources/css/marketing.css', 'resources/js/marketing.js'])

    @stack('head')
</head>
<body class="marketing-body antialiased">
    <a class="mk-skip-link" href="#main-content">Skip to content</a>

    <x-marketing.navbar />

    <main id="main-content">
        {{ $slot }}
    </main>

    <x-marketing.footer />

    <a
        href="#main-content"
        class="mk-scroll-top"
        data-mk-scroll-top
        aria-label="Back to top"
    >
        <x-marketing.icon name="arrow-up" size="sm" />
    </a>

    @stack('scripts')
</body>
</html>
