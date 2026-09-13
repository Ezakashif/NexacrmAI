@props([
    'id',
    'question',
])

<div class="mk-faq-item">
    <h3>
        <button
            type="button"
            class="mk-faq-trigger"
            @click="toggle('{{ $id }}')"
            :aria-expanded="isOpen('{{ $id }}').toString()"
            :id="'faq-button-{{ $id }}'"
            :aria-controls="'faq-panel-{{ $id }}'"
        >
            <span class="text-base font-semibold text-slate-900">{{ $question }}</span>
            <span
                class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-600 transition"
                :class="isOpen('{{ $id }}') ? 'rotate-180 bg-sky-50 text-sky-700' : ''"
                aria-hidden="true"
            >
                <x-marketing.icon name="chevron-down" size="sm" />
            </span>
        </button>
    </h3>
    <div
        x-cloak
        x-show="isOpen('{{ $id }}')"
        x-transition.opacity.duration.200ms
        id="faq-panel-{{ $id }}"
        role="region"
        aria-labelledby="faq-button-{{ $id }}"
        class="mk-faq-panel"
    >
        {{ $slot }}
    </div>
</div>
