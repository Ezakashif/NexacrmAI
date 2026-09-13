@props([
    'title',
    'description' => null,
])

<section {{ $attributes->class(['crm-form-section']) }}>
    <h3 class="crm-form-section__title">{{ $title }}</h3>
    @if ($description)
        <p class="crm-form-section__desc">{{ $description }}</p>
    @endif
    <div class="crm-form-section__body">
        {{ $slot ?? '' }}
    </div>
</section>
