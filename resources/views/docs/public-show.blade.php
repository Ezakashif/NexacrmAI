<x-marketing-layout
    title="Documentation"
    description="Algos CRM documentation—installation, architecture, modules, channels, Super Admin, operations, and end-user guides."
>
    <section class="mk-atmosphere">
        <div class="mk-container mk-section pb-8 md:pb-10">
            <div class="mk-hero-copy mx-auto max-w-3xl text-center">
                <p class="mk-brand-hero mk-brand-hero-page mb-4" aria-label="{{ config('marketing.name') }}">
                    {{ strtolower(config('marketing.name')) }}<span class="dot">.</span>
                </p>
                <h1 class="mk-display mk-page-title">CRM Documentation</h1>
                <p class="mk-lead mx-auto mt-4 max-w-2xl">
                    The same product docs available in the dashboard—guides for setup, architecture, modules, and day-to-day use.
                </p>
            </div>
        </div>
    </section>

    <section class="mk-section bg-slate-50 pt-0">
        <div class="mk-container">
            @include('docs._viewer')
        </div>
    </section>
</x-marketing-layout>
