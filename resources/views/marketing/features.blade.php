@php
    $page = config('marketing.features_page');
    $trialAvailable = \App\Support\MarketingCta::trialAvailable();
    $trialHref = \App\Support\MarketingCta::trialHref();
    $demoHref = \App\Support\MarketingCta::demoHref();
    $liveDemoHref = \App\Support\MarketingCta::liveDemoHref();
    $primaryHref = \App\Support\MarketingCta::primaryHref();
@endphp

<x-marketing-layout
    title="Features"
    description="Explore every {{ config('marketing.name') }} module—leads, customers, tasks, kanban, reports, permissions, and multi-tenant admin."
>
    {{-- Hero --}}
    <section class="mk-atmosphere">
        <div class="mk-container mk-section pb-12 md:pb-16">
            <div class="mk-hero-copy mx-auto max-w-3xl text-center">
                <p class="mk-brand-hero mk-brand-hero-page mb-5" aria-label="{{ config('marketing.name') }}">
                    {{ strtolower(config('marketing.name')) }}<span class="dot">.</span>
                </p>
                <h1 class="mk-display mk-page-title">
                    {{ $page['headline'] }}
                </h1>
                <p class="mk-lead mx-auto mt-5 max-w-2xl">
                    {{ $page['subheadline'] }}
                </p>
                <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    @if ($trialAvailable)
                        <x-marketing.button :href="$trialHref" size="lg">
                            <x-marketing.trial-cta-label />
                            <x-marketing.icon name="arrow-right" size="sm" />
                        </x-marketing.button>
                        <x-marketing.button :href="$liveDemoHref" variant="secondary" size="lg">
                            Try Live Demo
                        </x-marketing.button>
                    @else
                        <x-marketing.button :href="$liveDemoHref" size="lg">
                            Try Live Demo
                            <x-marketing.icon name="arrow-right" size="sm" />
                        </x-marketing.button>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- Module groups --}}
    @foreach ($page['groups'] as $index => $group)
        <section
            id="{{ $group['id'] }}"
            class="mk-section {{ $index % 2 === 1 ? 'mk-section-muted' : 'bg-white' }}"
            aria-labelledby="group-{{ $group['id'] }}"
        >
            <div class="mk-container">
                <div data-mk-reveal>
                    <x-marketing.section-heading
                        :heading-id="'group-'.$group['id']"
                        eyebrow="Modules"
                        :title="$group['title']"
                        :description="$group['description']"
                    />
                </div>

                <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($group['modules'] as $moduleIndex => $module)
                        <div data-mk-reveal style="--mk-reveal-delay: {{ ($moduleIndex + 1) * 100 }}ms">
                            <x-marketing.feature-module
                                :icon="$module['icon']"
                                :title="$module['title']"
                                :description="$module['description']"
                                :highlights="$module['highlights']"
                            />
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endforeach

    {{-- Closing CTA --}}
    <x-marketing.cta
        title="See {{ config('marketing.name') }} in your workflow"
    />
</x-marketing-layout>
