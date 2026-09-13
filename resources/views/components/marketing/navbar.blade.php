@php
    $homeUrl = route('marketing.home');
    $navItems = [
        ['label' => 'Product', 'href' => $homeUrl.'#product-showcase-heading'],
        ['label' => 'Features', 'href' => $homeUrl.'#features-heading'],
        ['label' => 'Pricing', 'href' => $homeUrl.'#pricing-heading'],
        ['label' => 'FAQ', 'href' => $homeUrl.'#faq-heading'],
        ['label' => 'Contact', 'href' => $homeUrl.'#contact'],
    ];
    $trialAvailable = \App\Support\MarketingCta::trialAvailable();
    $trialHref = \App\Support\MarketingCta::trialHref();
    $demoHref = \App\Support\MarketingCta::demoHref();
    $liveDemoHref = \App\Support\MarketingCta::liveDemoHref();
@endphp

<header class="mk-nav" data-mk-nav x-data="marketingNav" @keydown.escape.window="close()">
    <div class="mk-container flex h-full items-center justify-between gap-4">
        <div class="flex items-center gap-8">
            <x-marketing.logo />

            <nav class="hidden items-center gap-6 lg:flex" aria-label="Primary">
                @foreach ($navItems as $item)
                    <a
                        href="{{ $item['href'] }}"
                        class="mk-nav-link"
                    >
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>
        </div>

        <div class="hidden items-center gap-3 lg:flex">
            @auth
                <x-marketing.button
                    href="{{ auth()->user()->isSuperAdmin() ? route('superadmin.dashboard') : route('dashboard') }}"
                    variant="secondary"
                    size="sm"
                >
                    Go to app
                </x-marketing.button>
            @else
                <a href="{{ $liveDemoHref }}" class="mk-nav-link">
                    Try Live Demo
                </a>
                <x-marketing.button href="{{ route('login') }}" variant="ghost" size="sm">
                    Log in
                </x-marketing.button>
                @if ($trialAvailable)
                    <x-marketing.button href="{{ $trialHref }}" size="sm">
                        <x-marketing.trial-cta-label />
                    </x-marketing.button>
                @endif
            @endauth
        </div>

        <button
            type="button"
            class="mk-btn mk-btn-ghost mk-btn-sm lg:hidden"
            @click="toggle()"
            :aria-expanded="open.toString()"
            aria-controls="mobile-nav"
            aria-label="Toggle navigation"
        >
            <x-marketing.icon name="menu" x-show="!open" />
            <x-marketing.icon name="x" x-cloak x-show="open" />
        </button>
    </div>

    <div
        id="mobile-nav"
        x-cloak
        x-show="open"
        x-transition.opacity
        class="border-t border-slate-200 bg-white lg:hidden"
        @click.outside="close()"
    >
        <nav class="mk-container flex flex-col gap-1 py-4" aria-label="Mobile">
            @foreach ($navItems as $item)
                <a
                    href="{{ $item['href'] }}"
                    class="rounded-lg px-3 py-3 text-base font-medium text-slate-700 hover:bg-slate-50"
                    @click="close()"
                >
                    {{ $item['label'] }}
                </a>
            @endforeach

            <div class="mt-3 flex flex-col gap-2 border-t border-slate-100 pt-4">
                @auth
                    <x-marketing.button
                        href="{{ auth()->user()->isSuperAdmin() ? route('superadmin.dashboard') : route('dashboard') }}"
                        variant="secondary"
                        class="w-full"
                    >
                        Go to app
                    </x-marketing.button>
                @else
                    <x-marketing.button href="{{ $liveDemoHref }}" variant="ghost" class="w-full">
                        Try Live Demo
                    </x-marketing.button>
                    <x-marketing.button href="{{ route('login') }}" variant="secondary" class="w-full">
                        Log in
                    </x-marketing.button>
                    @if ($trialAvailable)
                        <x-marketing.button href="{{ $trialHref }}" class="w-full">
                            <x-marketing.trial-cta-label />
                        </x-marketing.button>
                    @endif
                @endauth
            </div>
        </nav>
    </div>
</header>
