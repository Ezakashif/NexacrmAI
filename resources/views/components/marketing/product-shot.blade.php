@props([
    'title',
    'icon' => 'layout-dashboard',
    'image' => null,
    'alt' => null,
])

@php
    $altText = $alt ?? ($title.' screenshot');
@endphp

<figure {{ $attributes->class(['mk-product-shot']) }}>
    @if ($image)
        <img
            src="{{ $image }}"
            alt="{{ $altText }}"
            class="mk-product-shot-image"
            loading="lazy"
            decoding="async"
        >
    @else
        <div
            class="mk-product-shot-placeholder"
            role="img"
            aria-label="{{ $altText }}"
        >
            <div class="mk-product-shot-chrome">
                <span></span><span></span><span></span>
                <div class="mk-product-shot-url">app.algos.com/{{ \Illuminate\Support\Str::slug($title) }}</div>
            </div>
            <div class="mk-product-shot-body">
                <div class="mk-product-shot-aside" aria-hidden="true">
                    <div class="mk-product-shot-brand">algos.</div>
                    <div class="is-active">{{ $title }}</div>
                    <div>Overview</div>
                    <div>Details</div>
                    <div>Settings</div>
                </div>
                <div class="mk-product-shot-main">
                    <div class="mk-product-shot-heading">
                        <span class="mk-icon-well h-10 w-10">
                            <x-marketing.icon :name="$icon" />
                        </span>
                        <div>
                            <div class="font-semibold text-slate-900">{{ $title }}</div>
                            <div class="text-xs text-slate-500">Workspace overview</div>
                        </div>
                    </div>
                    <div class="mk-product-shot-grid" aria-hidden="true">
                        <div></div>
                        <div></div>
                        <div></div>
                        <div class="span-2"></div>
                        <div></div>
                    </div>
                    <p class="mk-product-shot-hint">A focused view of the information your team needs next.</p>
                </div>
            </div>
        </div>
    @endif
</figure>
