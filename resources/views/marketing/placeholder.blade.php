<x-marketing-layout :title="$title" :description="$description">
    <section class="mk-atmosphere">
        <div class="mk-container mk-section">
            <div class="mk-fade-up max-w-2xl">
                <p class="mk-eyebrow mb-3">Coming next</p>
                <h1 class="mk-display text-4xl sm:text-5xl">{{ $heading }}</h1>
                <p class="mk-lead mt-5">{{ $body }}</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <x-marketing.button href="{{ route('marketing.home') }}" variant="secondary">
                        Back to foundation
                    </x-marketing.button>
                    <x-marketing.button href="{{ \App\Support\MarketingCta::liveDemoHref() }}">
                        Try Live Demo
                    </x-marketing.button>
                </div>
            </div>
        </div>
    </section>
</x-marketing-layout>
