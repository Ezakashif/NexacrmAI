@props([
    'name',
    'description' => '',
    'monthly' => 0,
    'annual' => 0,
    'features' => [],
    'cta' => 'Start free trial',
    'ctaType' => 'trial',
    'ctaHref' => null,
    'highlighted' => false,
    'billing' => 'monthly',
    'currency' => 'USD',
    'trialDays' => 0,
])

@php
    $ctaHref = $ctaHref ?? \App\Support\MarketingCta::primaryHref();
    $price = $billing === 'annual' ? $annual : $monthly;
@endphp

<article @class([
    'mk-card relative flex h-full min-h-[32rem] flex-col p-6 sm:p-7',
    'mk-pricing-featured' => $highlighted,
    'mk-card-interactive' => ! $highlighted,
])>
    @if ($highlighted)
        <span class="mk-pricing-badge">{{ config('marketing.pricing.recommended_label', 'Recommended') }}</span>
    @endif

    <div>
        <h3 class="text-xl font-bold tracking-tight text-slate-900">{{ $name }}</h3>
        @if ($description)
            <p class="mt-2 text-sm text-slate-600">{{ $description }}</p>
        @endif
    </div>

    <div class="mt-6">
        <div class="flex items-baseline gap-1">
            <span class="text-4xl font-bold tracking-tight text-slate-900">{{ $currency }} {{ number_format((float) $price, 2) }}</span>
            <span class="text-sm text-slate-500">/user/mo</span>
        </div>
        @if ($billing === 'annual')
            <p class="mt-1 text-xs font-medium text-sky-700">Billed annually</p>
        @else
            <p class="mt-1 text-xs text-slate-400">Billed monthly</p>
        @endif
    </div>

    <ul class="mt-6 max-h-52 flex-1 space-y-3 overflow-y-auto pr-1">
        @foreach ($features as $feature)
            <li class="flex items-start gap-2.5 text-sm text-slate-700">
                <span class="mk-icon-well mt-0.5 h-5 w-5 text-[0.7rem] text-emerald-700" style="background: #ecfdf5;">
                    <x-marketing.icon name="check" size="sm" />
                </span>
                <span>{{ $feature }}</span>
            </li>
        @endforeach
    </ul>

    <div class="mt-8 space-y-2">
        <x-marketing.button
            :href="$ctaHref"
            :variant="$highlighted ? 'primary' : 'secondary'"
            class="w-full"
        >
            @if ($ctaType === 'trial')
                <x-marketing.trial-cta-label :trial-days="$trialDays" />
            @else
                {{ $cta }}
            @endif
        </x-marketing.button>
        @if ($highlighted)
            <p class="text-center text-xs text-slate-500">{{ $trialDays > 0 ? $trialDays.' '.Illuminate\Support\Str::plural('day', $trialDays).' free trial' : config('marketing.pricing.trial_note') }}</p>
        @endif
    </div>
</article>
