@props([
    'items' => [],
])

@if (count($items))
    <ul {{ $attributes->class(['mk-trust-chips']) }} role="list">
        @foreach ($items as $item)
            <li class="mk-trust-chip">
                <span class="mk-trust-chip-icon" aria-hidden="true">
                    <x-marketing.icon name="check" size="sm" />
                </span>
                <span>{{ $item }}</span>
            </li>
        @endforeach
    </ul>
@endif
