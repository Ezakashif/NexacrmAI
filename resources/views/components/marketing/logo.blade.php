@props([
    'href' => null,
])

@php
    $destination = $href ?? route('marketing.home');
    $brand = app(\App\Services\SuperAdmin\PlatformSettingsService::class)->platformName();
@endphp

<a href="{{ $destination }}" {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5 no-underline']) }} aria-label="{{ $brand }} home">
    <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-slate-900 text-sky-400 shadow-sm" aria-hidden="true">
        <x-marketing.mark class="h-5 w-5" />
    </span>
    <span class="text-xl font-bold tracking-tight text-slate-900">
        {{ strtolower($brand) }}<span class="text-sky-500">.</span>
    </span>
</a>
