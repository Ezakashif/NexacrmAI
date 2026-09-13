@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'type' => 'button',
])

@php
    $variants = [
        'primary' => 'mk-btn-primary',
        'secondary' => 'mk-btn-secondary',
        'ghost' => 'mk-btn-ghost',
        'soft' => 'mk-btn-accent-soft',
        'on-dark' => 'mk-btn-on-dark',
    ];
    $sizes = [
        'sm' => 'mk-btn-sm',
        'md' => 'mk-btn-md',
        'lg' => 'mk-btn-lg',
    ];
    $classes = trim('mk-btn '.($variants[$variant] ?? $variants['primary']).' '.($sizes[$size] ?? $sizes['md']));
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
