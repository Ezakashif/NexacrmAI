@props([
    'href' => null,
])

@php
    $destination = $href ?? route('marketing.home');
    $brand = app(\App\Services\SuperAdmin\PlatformSettingsService::class)->platformName();
@endphp

<a href="{{ $destination }}" {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5 no-underline']) }} aria-label="{{ $brand }} home">
    <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-slate-900 text-sky-400 shadow-sm" aria-hidden="true">
        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 4 L5 20" />
            <path d="M12 4 L19 20" />
            <path d="M8.5 14.5 H15.5" />
        </svg>
    </span>
    <span class="text-xl font-bold tracking-tight text-slate-900">
        {{ strtolower($brand) }}<span class="text-sky-500">.</span>
    </span>
</a>
