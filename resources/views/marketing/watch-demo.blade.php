@php
    $brand = config('marketing.name');
    $liveDemoHref = \App\Support\MarketingCta::liveDemoHref();
    $trialHref = \App\Support\MarketingCta::trialHref();
    $videoSrc = \App\Support\MarketingCta::watchDemoSrc();
@endphp

<x-marketing-layout
    title="Watch Demo"
    description="Watch a short walkthrough of {{ $brand }}—leads, pipeline, WhatsApp conversations, and reports in one workspace."
>
    <section class="mk-atmosphere">
        <div class="mk-container mk-section pb-8 md:pb-12">
            <div class="mk-hero-copy mx-auto max-w-3xl text-center">
                <p class="mk-brand-hero mk-brand-hero-page mb-5" aria-label="{{ $brand }}">
                    {{ config('marketing.wordmark', 'nexacrm.ai') }}<span class="dot">.</span>
                </p>
                <h1 class="mk-display mk-page-title">
                    See {{ $brand }} in action
                </h1>
                <p class="mk-lead mx-auto mt-5 max-w-2xl">
                    A one-minute walkthrough of the workspace your team uses every day.
                </p>
            </div>
        </div>
    </section>

    <section class="mk-section bg-white pt-0" aria-labelledby="watch-demo-heading">
        <div class="mk-container">
            <h2 id="watch-demo-heading" class="sr-only">Product demo video</h2>
            <div class="mx-auto max-w-5xl overflow-hidden rounded-2xl bg-slate-950 shadow-xl ring-1 ring-slate-200" data-mk-reveal>
                <video
                    class="aspect-video w-full bg-slate-950"
                    controls
                    playsinline
                    preload="metadata"
                    controlslist="nodownload"
                >
                    <source src="{{ $videoSrc }}" type="video/mp4">
                    Your browser does not support the video tag.
                    <a href="{{ $videoSrc }}" class="text-sky-400 underline">Download the demo</a>
                </video>
            </div>

            <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                <x-marketing.button :href="$liveDemoHref" size="lg">
                    Try Live Demo
                    <x-marketing.icon name="arrow-right" size="sm" />
                </x-marketing.button>
                @if ($trialHref)
                    <x-marketing.button :href="$trialHref" variant="secondary" size="lg">
                        <x-marketing.trial-cta-label />
                    </x-marketing.button>
                @endif
            </div>
        </div>
    </section>
</x-marketing-layout>
