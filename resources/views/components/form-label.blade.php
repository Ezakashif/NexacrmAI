@props([
    'required' => false,
])

<label {{ $attributes->merge(['class' => 'mb-1']) }}>
    {{ $slot ?? '' }}
    @if ($required)
        <span class="crm-required" title="Required" aria-hidden="true">*</span>
        <span class="sr-only">(required)</span>
    @endif
</label>
