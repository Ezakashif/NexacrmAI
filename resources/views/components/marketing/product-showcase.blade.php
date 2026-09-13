@props([
    'headline' => 'See the CRM in Action',
    'subheadline' => null,
    'items' => [],
    'trialHref' => null,
])

@php
    $trialHref = $trialHref ?? \App\Support\MarketingCta::primaryHref();
    $priorityIds = ['dashboard', 'leads', 'customers', 'tasks', 'reports'];
    $itemsById = collect($items)->keyBy('id');
    $featuredItems = collect($priorityIds)
        ->map(fn ($id) => $itemsById->get($id))
        ->filter()
        ->values();
    $additionalItems = collect($items)
        ->reject(fn ($item) => in_array($item['id'], $priorityIds, true))
        ->values();
    $trialAvailable = \App\Support\MarketingCta::trialAvailable();
@endphp

<section {{ $attributes->class(['mk-section mk-product-showcase']) }} aria-labelledby="product-showcase-heading">
    <div class="mk-container">
        <div data-mk-reveal>
            <x-marketing.section-heading
                heading-id="product-showcase-heading"
                eyebrow="Product"
                :title="$headline"
                :description="$subheadline"
                align="center"
            />
        </div>

        <div class="mt-12 space-y-16 md:space-y-20 lg:space-y-24">
            @foreach ($featuredItems as $index => $item)
                @php
                    $imageLeft = $index % 2 === 0;
                @endphp
                <article
                    id="showcase-{{ $item['id'] }}"
                    class="mk-showcase-row {{ $imageLeft ? 'is-image-left' : 'is-image-right' }}"
                    data-mk-reveal
                    style="--mk-reveal-delay: {{ ($index % 4) * 80 }}ms"
                >
                    <div class="mk-showcase-media">
                        <x-marketing.product-shot
                            :title="$item['title']"
                            :icon="$item['icon'] ?? 'layout-dashboard'"
                            :image="$item['image'] ?? null"
                            :alt="'Algos '.$item['title'].' screenshot'"
                        />
                    </div>

                    <div class="mk-showcase-copy">
                        <p class="mk-showcase-index">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</p>
                        <h3 class="mk-showcase-title">{{ $item['title'] }}</h3>
                        <p class="mk-showcase-benefit">{{ $item['benefit'] }}</p>
                        <div class="mt-6">
                            <x-marketing.button :href="$trialHref">
                                @if ($trialAvailable)
                                    <x-marketing.trial-cta-label />
                                @else
                                    Try Live Demo
                                @endif
                                <x-marketing.icon name="arrow-right" size="sm" />
                            </x-marketing.button>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        @if ($additionalItems->isNotEmpty())
            <section
                class="mk-showcase-more mt-16 md:mt-20"
                aria-labelledby="product-more-heading"
                x-data="{ active: '{{ $additionalItems->first()['id'] }}' }"
            >
                <div class="mk-showcase-more-intro">
                    <p class="mk-eyebrow">More of the workspace</p>
                    <h3 id="product-more-heading">Explore every part of the CRM</h3>
                    <p>Switch between the tools that keep your team organized after the core sales workflow is in motion.</p>
                </div>

                <div class="mk-showcase-tabs" role="tablist" aria-label="Additional Algos CRM modules">
                    @foreach ($additionalItems as $item)
                        <button
                            type="button"
                            class="mk-showcase-tab"
                            id="showcase-tab-{{ $item['id'] }}"
                            role="tab"
                            :aria-selected="active === '{{ $item['id'] }}'"
                            :tabindex="active === '{{ $item['id'] }}' ? 0 : -1"
                            aria-controls="showcase-panel-{{ $item['id'] }}"
                            @click="active = '{{ $item['id'] }}'"
                        >
                            <x-marketing.icon :name="$item['icon'] ?? 'layout-dashboard'" size="sm" />
                            {{ $item['title'] }}
                        </button>
                    @endforeach
                </div>

                <div class="mt-6">
                    @foreach ($additionalItems as $item)
                        <article
                            id="showcase-panel-{{ $item['id'] }}"
                            class="mk-showcase-more-panel"
                            role="tabpanel"
                            aria-labelledby="showcase-tab-{{ $item['id'] }}"
                            @if (! $loop->first) x-cloak @endif
                            x-show="active === '{{ $item['id'] }}'"
                            x-transition.opacity.duration.200ms
                        >
                            <div class="mk-showcase-media">
                                <x-marketing.product-shot
                                    :title="$item['title']"
                                    :icon="$item['icon'] ?? 'layout-dashboard'"
                                    :image="$item['image'] ?? null"
                                    :alt="'Algos '.$item['title'].' workspace preview'"
                                />
                            </div>
                            <div class="mk-showcase-copy">
                                <p class="mk-showcase-index">CRM MODULE</p>
                                <h4 class="mk-showcase-title">{{ $item['title'] }}</h4>
                                <p class="mk-showcase-benefit">{{ $item['benefit'] }}</p>
                                <div class="mt-6">
                                    <x-marketing.button :href="$trialHref">
                                        @if ($trialAvailable)
                                            <x-marketing.trial-cta-label />
                                        @else
                                            Try Live Demo
                                        @endif
                                        <x-marketing.icon name="arrow-right" size="sm" />
                                    </x-marketing.button>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</section>
