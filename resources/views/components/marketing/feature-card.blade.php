@props([
    'title',
    'description' => null,
    'problem' => null,
    'solution' => null,
    'benefit' => null,
    'icon' => 'sparkles',
])

<article {{ $attributes->class(['mk-card mk-card-interactive p-6']) }}>
    <div class="mk-icon-well mb-4 h-11 w-11">
        <x-marketing.icon :name="$icon" />
    </div>
    <h3 class="text-lg font-semibold tracking-tight text-slate-900">{{ $title }}</h3>

    @if ($problem || $solution || $benefit)
        <dl class="mk-feature-flow mt-3 space-y-2.5">
            @if ($problem)
                <div>
                    <dt class="mk-feature-flow-label">Problem</dt>
                    <dd class="text-sm leading-relaxed text-slate-600">{{ $problem }}</dd>
                </div>
            @endif
            @if ($solution)
                <div>
                    <dt class="mk-feature-flow-label">Solution</dt>
                    <dd class="text-sm leading-relaxed text-slate-600">{{ $solution }}</dd>
                </div>
            @endif
            @if ($benefit)
                <div>
                    <dt class="mk-feature-flow-label">Business benefit</dt>
                    <dd class="text-sm font-medium leading-relaxed text-slate-800">{{ $benefit }}</dd>
                </div>
            @endif
        </dl>
    @elseif ($description)
        <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $description }}</p>
    @endif
</article>
