@php
    $contact = config('marketing.contact');
    $social = config('marketing.social');
    $brand = config('marketing.name');
    $trialAvailable = \App\Support\MarketingCta::trialAvailable();
    $trialHref = \App\Support\MarketingCta::trialHref();
    $primaryHref = $trialAvailable
        ? \App\Support\MarketingCta::primaryHref()
        : \App\Support\MarketingCta::liveDemoHref();
    $isDemo = ($intent ?? null) === 'demo';
    $defaultMessage = $isDemo
        ? 'Hi—I’d like to book a demo of '.$brand.' for our team.'
        : old('message');
@endphp

<x-marketing-layout
    title="Contact"
    :description="$isDemo ? 'Book a demo of Algos CRM with our team.' : 'Contact the Algos team about demos, trials, and partnerships.'"
>
    <section class="mk-atmosphere">
        <div class="mk-container mk-section pb-10 md:pb-12">
            <div class="mk-hero-copy mx-auto max-w-3xl text-center">
                <p class="mk-brand-hero mk-brand-hero-page mb-5" aria-label="{{ $brand }}">
                    {{ strtolower($brand) }}<span class="dot">.</span>
                </p>
                <h1 class="mk-display mk-page-title">
                    {{ $isDemo ? 'Book a demo' : 'Talk with our team' }}
                </h1>
                <p class="mk-lead mx-auto mt-5 max-w-2xl">
                    {{ $isDemo
                        ? 'Tell us about your team and we’ll schedule a walkthrough of the Algos workspace.'
                        : 'Questions about Algos, onboarding, or Enterprise plans? Send a message—we usually reply within one business day.' }}
                </p>
            </div>
        </div>
    </section>

    <section class="bg-white pb-16 md:pb-20" aria-labelledby="contact-form-heading">
        <div class="mk-container">
            <div class="grid gap-10 lg:grid-cols-[1.15fr_0.85fr] lg:items-start">
                <div class="mk-card p-6 sm:p-8" data-mk-reveal="left">
                    <h2 id="contact-form-heading" class="text-xl font-bold tracking-tight text-slate-900">
                        {{ $isDemo ? 'Request a demo' : 'Send a message' }}
                    </h2>
                    <p class="mt-2 text-sm text-slate-600">
                        All fields marked required help us route your note to the right person.
                    </p>

                    @if (session('status'))
                        <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if (session('warning'))
                        <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900" role="alert">
                            {{ session('warning') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('marketing.contact.store') }}" class="mt-6 space-y-5" novalidate>
                        @csrf
                        <input type="hidden" name="intent" value="{{ old('intent', $intent) }}">

                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label for="name" class="mk-label">Name <span class="text-sky-700">*</span></label>
                                <input
                                    id="name"
                                    name="name"
                                    type="text"
                                    value="{{ old('name') }}"
                                    autocomplete="name"
                                    required
                                    class="mk-input @error('name') border-red-400 @enderror"
                                    placeholder="Alex Morgan"
                                >
                                @error('name')
                                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="email" class="mk-label">Email <span class="text-sky-700">*</span></label>
                                <input
                                    id="email"
                                    name="email"
                                    type="email"
                                    value="{{ old('email') }}"
                                    autocomplete="email"
                                    required
                                    class="mk-input @error('email') border-red-400 @enderror"
                                    placeholder="alex@company.com"
                                >
                                @error('email')
                                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label for="company" class="mk-label">Company</label>
                                <input
                                    id="company"
                                    name="company"
                                    type="text"
                                    value="{{ old('company') }}"
                                    autocomplete="organization"
                                    class="mk-input @error('company') border-red-400 @enderror"
                                    placeholder="Acme Corp"
                                >
                                @error('company')
                                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="phone" class="mk-label">Phone</label>
                                <input
                                    id="phone"
                                    name="phone"
                                    type="tel"
                                    value="{{ old('phone') }}"
                                    autocomplete="tel"
                                    class="mk-input @error('phone') border-red-400 @enderror"
                                    placeholder="+1 (555) 000-0000"
                                >
                                @error('phone')
                                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label for="message" class="mk-label">Message <span class="text-sky-700">*</span></label>
                            <textarea
                                id="message"
                                name="message"
                                required
                                class="mk-input mk-textarea @error('message') border-red-400 @enderror"
                                placeholder="How can we help?"
                            >{{ $defaultMessage }}</textarea>
                            @error('message')
                                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                            <x-marketing.button type="submit" size="lg">
                                {{ $isDemo ? 'Request demo' : 'Send message' }}
                                <x-marketing.icon name="arrow-right" size="sm" />
                            </x-marketing.button>
                            <p class="text-xs text-slate-500">
                                By submitting, you agree we may email you about this inquiry.
                            </p>
                        </div>
                    </form>
                </div>

                <aside class="space-y-6">
                    <div class="mk-card p-6" data-mk-reveal="right" style="--mk-reveal-delay: 200ms">
                        <h2 class="text-lg font-bold tracking-tight text-slate-900">Business information</h2>
                        <ul class="mt-5 space-y-4 text-sm text-slate-600">
                            <li class="flex items-start gap-3">
                                <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-sky-50 text-sky-700">
                                    <x-marketing.icon name="mail" />
                                </span>
                                <div>
                                    <div class="font-semibold text-slate-900">Email</div>
                                    <a href="mailto:{{ $contact['email'] }}" class="hover:text-sky-700">{{ $contact['email'] }}</a>
                                </div>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-sky-50 text-sky-700">
                                    <x-marketing.icon name="phone" />
                                </span>
                                <div>
                                    <div class="font-semibold text-slate-900">Phone</div>
                                    <a href="tel:{{ preg_replace('/[^\d+]/', '', $contact['phone']) }}" class="hover:text-sky-700">{{ $contact['phone'] }}</a>
                                </div>
                            </li>
                            @if (filled($contact['address'] ?? null))
                                <li class="flex items-start gap-3">
                                    <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-sky-50 text-sky-700">
                                        <x-marketing.icon name="map-pin" />
                                    </span>
                                    <div>
                                        <div class="font-semibold text-slate-900">Address</div>
                                        <div>{{ $contact['address'] }}</div>
                                    </div>
                                </li>
                            @endif
                        </ul>

                        <div class="mt-6 border-t border-slate-100 pt-5">
                            <div class="text-sm font-semibold text-slate-900">Follow {{ $brand }}</div>
                            <div class="mt-3 flex items-center gap-2">
                                @foreach ([
                                    'linkedin' => 'LinkedIn',
                                    'facebook' => 'Facebook',
                                    'twitter' => 'X / Twitter',
                                    'github' => 'GitHub',
                                ] as $network => $label)
                                    @php($url = trim((string) ($social[$network] ?? '')))
                                    @if ($url !== '' && $url !== '#')
                                        <a href="{{ $url }}" class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-slate-600 transition hover:bg-sky-50 hover:text-sky-700" aria-label="{{ $label }}" rel="noopener noreferrer" target="_blank">
                                            <x-marketing.icon :name="$network" />
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>

                </aside>
            </div>
        </div>
    </section>

    <section class="mk-section mk-faq-section" aria-labelledby="contact-faq-heading">
        <div class="mk-container">
            <div class="mx-auto max-w-2xl text-center" data-mk-reveal>
                <x-marketing.section-heading
                    heading-id="contact-faq-heading"
                    eyebrow="FAQ"
                    title="Answers before you reach out"
                    description="Everything from trials and billing to data, permissions, and ongoing support."
                    align="center"
                />
            </div>
            <div class="mx-auto mt-10 max-w-3xl" data-mk-reveal style="--mk-reveal-delay: 160ms">
                <x-marketing.faq-accordion :items="$faqs" open="trial" class="mk-faq-surface" />
            </div>
            <div class="mt-10 text-center" data-mk-reveal>
                <x-marketing.button :href="$primaryHref">
                    @if ($trialAvailable)
                        <x-marketing.trial-cta-label />
                    @else
                        Try Live Demo
                    @endif
                    <x-marketing.icon name="arrow-right" size="sm" />
                </x-marketing.button>
            </div>
        </div>
    </section>

    <x-marketing.cta
        title="Prefer to explore on your own?"
        :description="$trialAvailable ? 'Start a free trial and invite your team when you’re ready.' : 'Explore the live CRM workspace, or watch a walkthrough of the product.'"
        secondary-label="View pricing"
        :secondary-href="route('marketing.pricing')"
    />
</x-marketing-layout>
