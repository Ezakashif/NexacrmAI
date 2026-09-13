@props([
    'name',
    'id' => null,
    'variant' => 'bootstrap',
    'autocomplete' => 'current-password',
    'required' => false,
    'autofocus' => false,
    'value' => null,
    'placeholder' => '••••••••',
])

@php
    $id = $id ?? $name;
    $baseClass = $variant === 'marketing' ? 'mk-input' : 'form-control';
@endphp

<div class="password-field password-field--{{ $variant }}" data-password-field>
    <input
        {{ $attributes->class([$baseClass])->merge([
            'type' => 'password',
            'name' => $name,
            'id' => $id,
            'autocomplete' => $autocomplete,
            'placeholder' => $placeholder,
        ]) }}
        @required($required)
        @if ($autofocus) autofocus @endif
        @if ($value !== null) value="{{ $value }}" @endif
    >

    <button
        type="button"
        class="password-field__toggle"
        data-password-toggle
        aria-label="Show password"
        aria-pressed="false"
        data-show-label="Show password"
        data-hide-label="Hide password"
    >
        <span class="password-field__icon password-field__icon--show" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                <path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/>
                <circle cx="12" cy="12" r="3"/>
            </svg>
        </span>
        <span class="password-field__icon password-field__icon--hide" aria-hidden="true" hidden>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                <path d="M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575 1 1 0 0 1 0 .696 10.747 10.747 0 0 1-1.444 2.49"/>
                <path d="M14.084 14.158a3 3 0 0 1-4.242-4.242"/>
                <path d="M17.479 17.499a10.75 10.75 0 0 1-15.417-5.151 1 1 0 0 1 0-.696 10.75 10.75 0 0 1 4.446-4.97"/>
                <path d="m2 2 20 20"/>
            </svg>
        </span>
    </button>
</div>
