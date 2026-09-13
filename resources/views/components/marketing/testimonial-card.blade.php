@props([
    'quote',
    'name',
    'role' => '',
    'company' => '',
    'initials' => null,
])

@php
    $initials = $initials ?? collect(explode(' ', $name))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('');
@endphp

<figure {{ $attributes->class(['mk-card flex h-full flex-col p-6']) }}>
    <x-marketing.icon name="quote" class="text-sky-500" />
    <blockquote class="mt-4 flex-1 text-base leading-relaxed text-slate-700">
        “{{ $quote }}”
    </blockquote>
    <figcaption class="mt-6 flex items-center gap-3">
        <span class="inline-flex h-11 w-11 items-center justify-center rounded-full bg-slate-900 text-sm font-semibold text-sky-300" aria-hidden="true">
            {{ $initials }}
        </span>
        <div>
            <div class="font-semibold text-slate-900">{{ $name }}</div>
            <div class="text-sm text-slate-500">
                {{ $role }}@if($role && $company), @endif{{ $company }}
            </div>
        </div>
    </figcaption>
</figure>
