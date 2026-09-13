@php
    $about = config('marketing.about');
    $brand = config('marketing.name');
@endphp

<x-marketing-layout
    title="About"
    description="Learn about Algos CRM—our mission, vision, story, timeline, and the technology behind the product."
>
    {{-- Hero --}}
    <section class="mk-atmosphere">
        <div class="mk-container mk-section pb-12 md:pb-16">
            <div class="mk-hero-copy mx-auto max-w-3xl text-center">
                <p class="mk-brand-hero mk-brand-hero-page mb-5" aria-label="{{ $brand }}">
                    {{ strtolower($brand) }}<span class="dot">.</span>
                </p>
                <h1 class="mk-display mk-page-title">
                    {{ $about['headline'] }}
                </h1>
                <p class="mk-lead mx-auto mt-5 max-w-2xl">
                    {{ $about['subheadline'] }}
                </p>
            </div>
        </div>
    </section>

    {{-- Mission & Vision --}}
    <section class="mk-section bg-white" aria-labelledby="mission-vision-heading">
        <div class="mk-container">
            <h2 id="mission-vision-heading" class="sr-only">Mission and vision</h2>
            <div class="grid gap-6 lg:grid-cols-2">
                <article class="mk-panel p-6 sm:p-8" data-mk-reveal="left">
                    <h3 class="mk-display text-2xl sm:text-3xl">{{ $about['mission']['title'] }}</h3>
                    <p class="mt-4 text-base leading-relaxed text-slate-600">{{ $about['mission']['body'] }}</p>
                </article>
                <article class="mk-panel p-6 sm:p-8" data-mk-reveal="right" style="--mk-reveal-delay: 200ms">
                    <h3 class="mk-display text-2xl sm:text-3xl">{{ $about['vision']['title'] }}</h3>
                    <p class="mt-4 text-base leading-relaxed text-slate-600">{{ $about['vision']['body'] }}</p>
                </article>
            </div>
        </div>
    </section>

    {{-- Why we built this --}}
    <section class="mk-section mk-section-muted" aria-labelledby="why-heading">
        <div class="mk-container grid gap-10 lg:grid-cols-[1.1fr_0.9fr] lg:items-start">
            <div>
                <x-marketing.section-heading
                    heading-id="why-heading"
                    eyebrow="Our story"
                    :title="$about['why']['title']"
                    :description="$about['why']['body']"
                />
            </div>
            <ul class="space-y-3">
                @foreach ($about['why']['points'] as $point)
                    <li class="flex items-start gap-3 border-b border-slate-200/80 px-1 py-3.5 text-sm font-medium text-slate-800 last:border-b-0">
                        <span class="mk-icon-well mt-0.5 h-5 w-5 shrink-0 text-emerald-700" style="background: #ecfdf5;">
                            <x-marketing.icon name="check" size="sm" />
                        </span>
                        <span>{{ $point }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- Timeline --}}
    <section class="mk-section bg-white" aria-labelledby="timeline-heading">
        <div class="mk-container">
            <x-marketing.section-heading
                heading-id="timeline-heading"
                eyebrow="Timeline"
                title="How Algos took shape"
                description="A short path from the problem to a product ready for growing teams."
                align="center"
            />

            <ol class="relative mx-auto max-w-3xl space-y-0">
                @foreach ($about['timeline'] as $index => $item)
                    <li class="relative grid gap-3 border-l border-slate-200 py-5 pl-8 sm:grid-cols-[7rem_1fr] sm:gap-6 sm:border-l-0 sm:pl-0">
                        <div class="absolute -left-[5px] top-7 h-2.5 w-2.5 rounded-full bg-sky-500 sm:hidden" aria-hidden="true"></div>
                        <div class="text-sm font-bold tracking-wide text-sky-700 sm:pt-1 sm:text-right">
                            {{ $item['year'] }}
                        </div>
                        <div class="sm:border-l sm:border-slate-200 sm:pl-8">
                            <div class="relative">
                                <div class="absolute -left-[37px] top-2 hidden h-2.5 w-2.5 rounded-full bg-sky-500 sm:block" aria-hidden="true"></div>
                                <h3 class="text-lg font-semibold tracking-tight text-slate-900">{{ $item['title'] }}</h3>
                                <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $item['description'] }}</p>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- Technology stack --}}
    <section class="mk-section mk-section-muted" aria-labelledby="stack-heading">
        <div class="mk-container">
            <x-marketing.section-heading
                heading-id="stack-heading"
                eyebrow="Technology"
                title="The stack behind Algos"
                description="A practical Laravel foundation—modern frontend for marketing, proven CRM shell for authenticated work."
                align="center"
            />

            <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($about['stack'] as $item)
                    <li class="rounded-xl border border-slate-200 bg-white px-5 py-4">
                        <div class="text-base font-semibold text-slate-900">{{ $item['name'] }}</div>
                        <div class="mt-1 text-sm text-slate-500">{{ $item['role'] }}</div>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    <x-marketing.cta
        title="Want to see Algos in action?"
    />
</x-marketing-layout>
