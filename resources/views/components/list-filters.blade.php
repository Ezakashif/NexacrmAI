@props(['resetUrl' => null])

@php
    $hasFilters = collect(request()->query())
        ->except('page')
        ->filter(fn ($value) => filled($value))
        ->isNotEmpty();
@endphp

<div class="card card-outline card-secondary mb-3 crm-filter-card">
    <div class="card-body py-3 px-3">
        <form method="GET" action="{{ url()->current() }}">
            <div class="row align-items-end">
                {{ $slot }}
                <div class="col-md-auto mb-2">
                    <button type="submit" class="btn btn-primary btn-sm mr-1" aria-label="Apply filters">
                        <i class="fas fa-search" aria-hidden="true"></i> Filter
                    </button>
                    @if($hasFilters)
                        <a href="{{ $resetUrl ?? url()->current() }}" class="btn btn-default btn-sm" aria-label="Clear filters">
                            <i class="fas fa-times" aria-hidden="true"></i> Clear
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>
