@props([
    'class' => 'h-5 w-5',
])

<svg viewBox="0 0 24 24" {{ $attributes->merge(['class' => $class]) }} fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <path d="M7 20 V4 L17 20 V4" />
</svg>
