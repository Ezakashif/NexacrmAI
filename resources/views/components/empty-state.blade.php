@props([
    'icon' => 'fas fa-inbox',
    'title',
    'description' => null,
    'actionUrl' => null,
    'actionLabel' => null,
])

{{-- Shared empty state (same markup as dashboard.empty-state) --}}
<div {{ $attributes->class(['crm-empty']) }}>
    <div class="crm-empty__icon" aria-hidden="true">
        <i class="{{ $icon }}"></i>
    </div>
    <p class="crm-empty__title">{{ $title }}</p>
    @if ($description)
        <p class="crm-empty__desc">{{ $description }}</p>
    @endif
    @if ($actionUrl && $actionLabel)
        <div class="crm-empty__action">
            <a href="{{ $actionUrl }}" class="btn btn-sm btn-primary">{{ $actionLabel }}</a>
        </div>
    @endif
    {{ $slot ?? '' }}
</div>
