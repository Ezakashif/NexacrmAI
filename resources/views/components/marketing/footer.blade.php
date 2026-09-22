@php
    $brand = config('marketing.name');
    $contact = config('marketing.contact');
    $social = config('marketing.social');
    $platformSettings = app(\App\Services\SuperAdmin\PlatformSettingsService::class);
    $logoUrl = $platformSettings->logoUrl('light') ?: $platformSettings->logoUrl();

    $socialLinks = collect([
        'linkedin' => ['url' => $social['linkedin'] ?? null, 'label' => 'LinkedIn'],
        'facebook' => ['url' => $social['facebook'] ?? null, 'label' => 'Facebook'],
        'twitter' => ['url' => $social['twitter'] ?? null, 'label' => 'X / Twitter'],
        'github' => ['url' => $social['github'] ?? null, 'label' => 'GitHub'],
    ])->filter(function (array $item) {
        $url = trim((string) ($item['url'] ?? ''));

        return $url !== '' && $url !== '#';
    });
@endphp



<footer class="border-t border-slate-200 bg-slate-900 text-slate-300">
    <div class="mk-container py-14">
        <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-5">
            <div class="sm:col-span-2 lg:col-span-2">
                <a href="{{ route('marketing.home') }}" class="inline-flex items-center gap-2.5 no-underline">
                    @if ($logoUrl)
                        <img src="{{ $logoUrl }}" alt="{{ $brand }}" class="h-9 w-auto max-w-[10rem] object-contain">
                    @else
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-slate-800 text-sky-400" aria-hidden="true">
                            <x-marketing.mark class="h-5 w-5" />
                        </span>
                        <span class="text-xl font-bold tracking-tight text-white">
                            {{ config('marketing.wordmark', 'nexacrm.ai') }}
                        </span>
                    @endif
                </a>
                <p class="mt-4 max-w-xs text-sm leading-relaxed text-slate-400">
                    {{ config('marketing.tagline') }}
                </p>
            </div>

            
            <div>
                <h2 class="text-sm font-semibold uppercase tracking-wide text-white">Product</h2>
                <ul class="mt-4 space-y-2.5">
                    <li>
                        <a href="{{ route('marketing.features') }}" class="text-sm text-slate-400 transition hover:text-white">Features</a>
                    </li>
                    <li>
                        <a href="{{ route('marketing.pricing') }}" class="text-sm text-slate-400 transition hover:text-white">Pricing</a>
                    </li>
                    <li>
                        <a href="{{ route('marketing.home') }}#product-showcase-heading" class="text-sm text-slate-400 transition hover:text-white">Product tour</a>
                    </li>
                    <li>
                        <a href="{{ route('marketing.contact', ['intent' => 'demo']) }}" class="text-sm text-slate-400 transition hover:text-white">Book a demo</a>
                    </li>
                </ul>
            </div>

            <div>
                <h2 class="text-sm font-semibold uppercase tracking-wide text-white">Resources</h2>
                <ul class="mt-4 space-y-2.5">
                    <li>
                        <a href="{{ route('marketing.about') }}" class="text-sm text-slate-400 transition hover:text-white">About</a>
                    </li>
                    <li>
                        <a href="{{ route('marketing.contact') }}" class="text-sm text-slate-400 transition hover:text-white">Contact</a>
                    </li>
                    <li>
                        <a href="{{ route('marketing.contact') }}" class="text-sm text-slate-400 transition hover:text-white">Help Center</a>
                    </li>
                    <li>
                        <a href="{{ route('marketing.documentation') }}" class="text-sm text-slate-400 transition hover:text-white">Documentation</a>
                    </li>
                </ul>
            </div>

            <div>
                <h2 class="text-sm font-semibold uppercase tracking-wide text-white">Company</h2>
                <ul class="mt-4 space-y-2.5 text-sm text-slate-400">
                    @if (filled($contact['email'] ?? null))
                        <li class="flex items-start gap-2">
                            <x-marketing.icon name="mail" size="sm" class="mt-0.5 text-slate-500" />
                            <a href="mailto:{{ $contact['email'] }}" class="hover:text-white">{{ $contact['email'] }}</a>
                        </li>
                    @endif
                    @if (filled($contact['phone'] ?? null))
                        <li class="flex items-start gap-2">
                            <x-marketing.icon name="phone" size="sm" class="mt-0.5 text-slate-500" />
                            <a href="tel:{{ preg_replace('/[^\d\+]/', '', $contact['phone']) }}" class="hover:text-white">{{ $contact['phone'] }}</a>
                        </li>
                    @endif
                    @if (filled($contact['address'] ?? null))
                        <li class="flex items-start gap-2">
                            <x-marketing.icon name="map-pin" size="sm" class="mt-0.5 text-slate-500" />
                            <span>{{ $contact['address'] }}</span>
                        </li>
                    @endif
                      @if ($socialLinks->isNotEmpty())
                    <div class="mt-5 flex items-center gap-3">
                        @foreach ($socialLinks as $name => $item)
                            <a href="{{ $item['url'] }}" class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-slate-800 text-slate-300 transition hover:bg-slate-700 hover:text-white" aria-label="{{ $item['label'] }}" rel="noopener noreferrer" target="_blank">
                                <x-marketing.icon :name="$name" />
                            </a>
                        @endforeach
                    </div>
                @endif
                </ul>
            </div>
        </div>

        <div class="mt-12 flex flex-col gap-3 border-t border-slate-800 pt-6 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between">
            <p>&copy; {{ date('Y') }} {{ $brand }}. All rights reserved.</p>
            <div class="flex flex-wrap gap-4">
                <a href="{{ route('marketing.contact') }}" class="transition hover:text-white">Privacy Policy</a>
                <a href="{{ route('marketing.contact') }}" class="transition hover:text-white">Terms</a>
                <a href="{{ route('marketing.contact') }}" class="transition hover:text-white">Contact</a>
            </div>
        </div>
    </div>
</footer>
