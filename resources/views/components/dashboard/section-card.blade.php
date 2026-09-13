@props([
    'title',
    'badge' => null,
    'badgeTone' => null, // info|danger|success|warning
    'actionUrl' => null,
    'actionLabel' => 'View all',
    'padded' => false,
    'id' => null,
])

@php
    $badgeClass = match ($badgeTone) {
        'danger' => 'crm-card__badge--danger',
        'info' => 'crm-card__badge--info',
        'success' => 'crm-card__badge--success',
        'warning' => 'crm-card__badge--warning',
        default => '',
    };
@endphp

<section @if ($id) id="{{ $id }}" @endif {{ $attributes->class(['crm-card']) }}>
    <header class="crm-card__header">
        <h3 class="crm-card__title">{{ $title }}</h3>
        <div class="crm-card__tools">
            @if ($badge !== null)
                <span class="crm-card__badge {{ $badgeClass }}" aria-label="{{ $badge }} items">{{ $badge }}</span>
            @endif
            @if ($actionUrl)
                <a href="{{ $actionUrl }}" class="crm-card__link">{{ $actionLabel }}</a>
            @endif
            {{ $tools ?? '' }}
        </div>
    </header>
    <div class="crm-card__body {{ $padded ? 'crm-card__body--padded' : '' }}">
        {{ $slot }}
    </div>
</section>
