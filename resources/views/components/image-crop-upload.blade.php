@props([
    'name' => 'photo',
    'id' => null,
    'label' => 'Profile photo',
    'help' => 'Choose a photo, then drag it to adjust inside the frame. JPEG, PNG, GIF or WebP. Max 2 MB.',
    'previewUrl' => null,
    'aspectRatio' => 1,
    'required' => false,
])

@php
    $inputId = $id ?: $name;
    $rootId = 'icu-'.$inputId;
    $modalId = $rootId.'-modal';
    $hasError = $errors->has($name);
@endphp

@once
    @push('css')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css">
    @endpush
    @push('js')
        <script src="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.js"></script>
        <script src="{{ asset('js/image-crop-upload.js') }}"></script>
    @endpush
@endonce

<div
    {{ $attributes->class(['crm-image-crop', $hasError ? 'is-invalid' : '']) }}
    id="{{ $rootId }}"
    data-image-crop-upload
    data-aspect-ratio="{{ $aspectRatio }}"
    data-modal-id="{{ $modalId }}"
    data-max-bytes="2097152"
>
    @if ($label)
        <x-form-label :for="$inputId" :required="$required">{{ $label }}</x-form-label>
    @endif

    <div class="crm-image-crop__body">
        <div class="crm-image-crop__preview-wrap" aria-hidden="true">
            <img
                class="crm-image-crop__preview"
                src="{{ $previewUrl ?: '' }}"
                alt=""
                @if (! $previewUrl) hidden @endif
                data-icu-preview
            >
            <div class="crm-image-crop__preview-placeholder" data-icu-placeholder @if ($previewUrl) hidden @endif>
                <i class="fas fa-user" aria-hidden="true"></i>
            </div>
        </div>

        <div class="crm-image-crop__controls">
            <div
                class="crm-image-crop__dropzone"
                data-icu-dropzone
                tabindex="0"
                role="button"
                aria-controls="{{ $inputId }}"
                aria-label="{{ __('Choose a photo or press Enter to browse') }}"
            >
                <input
                    type="file"
                    id="{{ $inputId }}"
                    name="{{ $name }}"
                    accept="image/jpeg,image/png,image/gif,image/webp"
                    class="crm-image-crop__input"
                    data-icu-input
                    @if ($required) required @endif
                >
                <div class="crm-image-crop__dropzone-inner" data-icu-drop-label>
                    <i class="fas fa-cloud-upload-alt" aria-hidden="true"></i>
                    <span class="crm-image-crop__dropzone-title">{{ __('Drop a photo here') }}</span>
                    <span class="crm-image-crop__dropzone-sub">{{ __('or click to browse, then drag to adjust in the frame') }}</span>
                </div>
            </div>

            <div class="crm-image-crop__actions" data-icu-actions @if (! $previewUrl) hidden @endif>
                <button type="button" class="btn btn-sm btn-outline-primary" data-icu-change>
                    <i class="fas fa-hand-paper" aria-hidden="true"></i> {{ __('Adjust in frame') }}
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-icu-clear>
                    <i class="fas fa-times" aria-hidden="true"></i> {{ __('Clear') }}
                </button>
            </div>

            @if ($help)
                <small class="form-text text-muted" data-icu-help>{{ $help }}</small>
            @endif
            <div class="invalid-feedback d-block" data-icu-error @if (! $hasError) hidden @endif>
                {{ $hasError ? $errors->first($name) : '' }}
            </div>
        </div>
    </div>

    <div class="modal fade" id="{{ $modalId }}" tabindex="-1" role="dialog" aria-labelledby="{{ $modalId }}-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="{{ $modalId }}-title">{{ __('Adjust photo in frame') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('Close') }}">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="crm-image-crop__hint mb-3">
                        <i class="fas fa-arrows-alt" aria-hidden="true"></i>
                        {{ __('Drag the photo to position it inside the frame. Use zoom to scale.') }}
                    </p>
                    <div class="crm-image-crop__frame-shell">
                        <div class="crm-image-crop__stage crm-image-crop__stage--framed">
                            <img src="" alt="" data-icu-crop-image>
                        </div>
                    </div>
                    <div class="crm-image-crop__zoom mt-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="mb-0 small text-muted" for="{{ $rootId }}-zoom">{{ __('Zoom') }}</label>
                            <span class="small text-muted">{{ __('Drag photo to adjust') }}</span>
                        </div>
                        <input id="{{ $rootId }}-zoom" type="range" min="0" max="1" step="0.01" value="0" data-icu-zoom class="w-100">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="button" class="btn btn-primary" data-icu-apply>
                        <i class="fas fa-check" aria-hidden="true"></i> {{ __('Use this photo') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
