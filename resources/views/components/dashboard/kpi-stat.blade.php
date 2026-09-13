@props([
    'label',
    'value',
    'href' => null,
    'meta' => 'View',
    'tone' => 'accent', // accent|warning|danger|success
])

@php
    $toneClass = match ($tone) {
        'warning' => 'crm-kpi--warning',
        'danger' => 'crm-kpi--danger',
        'success' => 'crm-kpi--success',
        default => '',
    };
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->class(['crm-kpi', $toneClass]) }}>
        <div class="crm-kpi__label">{{ $label }}</div>
        <div class="crm-kpi__value">{!! $value !!}</div>
        <div class="crm-kpi__meta"><span>{{ $meta }}</span> <span aria-hidden="true">→</span></div>
    </a>
@else
    <div {{ $attributes->class(['crm-kpi', $toneClass]) }}>
        <div class="crm-kpi__label">{{ $label }}</div>
        <div class="crm-kpi__value">{!! $value !!}</div>
        @if($meta)
            <div class="crm-kpi__meta">{{ $meta }}</div>
        @endif
    </div>
@endif
