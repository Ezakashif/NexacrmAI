@props([
    'address' => null,
])

@php
    $address = filled($address) ? $address : config('marketing.contact.address');
@endphp

@if (filled($address))
    <div {{ $attributes->class(['mk-card relative overflow-hidden']) }} role="img" aria-label="Map for {{ $address }}">
        <div class="absolute inset-0 bg-[linear-gradient(135deg,#e0f2fe_0%,#f8fafc_45%,#e2e8f0_100%)]"></div>
        <div
            class="absolute inset-0 opacity-40"
            style="background-image:
                linear-gradient(rgba(15,23,42,0.06) 1px, transparent 1px),
                linear-gradient(90deg, rgba(15,23,42,0.06) 1px, transparent 1px);
                background-size: 28px 28px;"
            aria-hidden="true"
        ></div>

        <div class="relative flex min-h-[240px] flex-col items-center justify-center gap-3 p-8 text-center sm:min-h-[280px]">
            <span class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-white text-sky-700 shadow-sm ring-1 ring-slate-200">
                <x-marketing.icon name="map-pin" size="lg" />
            </span>
            <div>
                <p class="mt-1 max-w-sm text-sm leading-relaxed text-slate-600">{{ $address }}</p>
            </div>
        </div>
    </div>
@endif
