@props([
    'title',
    'description',
    'icon' => 'sparkles',
    'highlights' => [],
    'category' => null,
])

<article {{ $attributes->class(['mk-card mk-card-interactive flex h-full flex-col p-6 sm:p-7']) }}>
    <div class="mb-4 flex items-start justify-between gap-3">
        <div class="mk-icon-well h-12 w-12">
            <x-marketing.icon :name="$icon" size="lg" />
        </div>
        @if ($category)
            <span class="rounded-md bg-slate-100 px-2 py-1 text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                {{ $category }}
            </span>
        @endif
    </div>

    <h3 class="text-xl font-semibold tracking-tight text-slate-900">{{ $title }}</h3>
    <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $description }}</p>

    @if (count($highlights) > 0)
        <ul class="mt-5 space-y-2.5 border-t border-slate-100 pt-5">
            @foreach ($highlights as $item)
                <li class="flex items-start gap-2.5 text-sm text-slate-700">
                    <span class="mk-icon-well mt-0.5 h-5 w-5 shrink-0 text-emerald-700" style="background: #ecfdf5;">
                        <x-marketing.icon name="check" size="sm" />
                    </span>
                    <span>{{ $item }}</span>
                </li>
            @endforeach
        </ul>
    @endif
</article>
