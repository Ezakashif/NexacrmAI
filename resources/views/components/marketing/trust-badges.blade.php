@props([
    'items' => [],
])

@if (count($items))
    <ul {{ $attributes->class(['mk-trust-badges']) }} role="list">
        @foreach ($items as $index => $item)
            <li
                class="mk-trust-badge"
                data-mk-reveal
                style="--mk-reveal-delay: {{ ($index + 1) * 60 }}ms"
            >
                <span class="mk-icon-well h-9 w-9 shrink-0">
                    <x-marketing.icon :name="$item['icon'] ?? 'shield'" size="sm" />
                </span>
                <span class="text-sm font-medium text-slate-700">{{ $item['label'] }}</span>
            </li>
        @endforeach
    </ul>
@endif
