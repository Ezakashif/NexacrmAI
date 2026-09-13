@props([
    'title',
    'subtitle' => null,
    'breadcrumbs' => [],
])

<div {{ $attributes->class(['crm-page-header']) }}>
    <div class="crm-page-header__main">
        @if (! empty($breadcrumbs))
            <x-breadcrumbs :items="$breadcrumbs" class="crm-breadcrumbs" />
        @endif
        <h1 class="crm-page-title">{{ $title }}</h1>
        @if ($subtitle)
            <span class="crm-page-subtitle">{{ $subtitle }}</span>
        @endif
    </div>
    @php
        $hasActions = isset($actions);
        $hasSlot = isset($slot) && trim((string) $slot) !== '';
    @endphp
    @if ($hasActions || $hasSlot)
        <div class="crm-header-actions">
            @if ($hasActions)
                {{ $actions }}
            @else
                {{ $slot }}
            @endif
        </div>
    @endif
</div>
