@props([
    'variant' => 'text',
    'width' => null,
    'height' => null,
    'lines' => 1,
])

@php
    $class = match ($variant) {
        'title' => 'crm-skeleton crm-skeleton--title',
        'avatar' => 'crm-skeleton crm-skeleton--avatar',
        'card' => 'crm-skeleton crm-skeleton--card',
        'row' => 'crm-skeleton crm-skeleton--row',
        default => 'crm-skeleton crm-skeleton--text',
    };

    $style = collect([
        $width ? "width: {$width}" : null,
        $height ? "height: {$height}" : null,
    ])->filter()->implode('; ');
@endphp

@if ($variant === 'text' && (int) $lines > 1)
    <div {{ $attributes->class(['crm-skeleton-group']) }} role="status" aria-label="Loading">
        @for ($i = 0; $i < (int) $lines; $i++)
            <span class="{{ $class }}" style="{{ $style }}{{ $i === (int) $lines - 1 ? '; width: 65%' : '' }}"></span>
        @endfor
        <span class="sr-only">Loading…</span>
    </div>
@else
    <span {{ $attributes->class([$class]) }} style="{{ $style }}" role="status" aria-label="Loading">
        <span class="sr-only">Loading…</span>
    </span>
@endif
