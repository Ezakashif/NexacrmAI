@extends('superadmin.layout')

@section('title', 'Demo visits')
@section('heading', 'Demo visits')
@section('subheading', 'People who started the public live demo')

@section('content')
@php
    $hasActiveFilters = collect($filters ?? [])
        ->filter(fn ($value) => filled($value))
        ->isNotEmpty();
@endphp

<div class="sa-toolbar">
    <div class="sa-toolbar__meta">
        <span class="sa-toolbar__count">{{ $visits->total() }} {{ \Illuminate\Support\Str::plural('visit', $visits->total()) }}</span>
        @if ($newCount > 0)
            <span class="badge badge-warning">{{ $newCount }} new</span>
        @endif
        @if ($hasActiveFilters)
            <span class="sa-toolbar__hint">Filtered results</span>
        @endif
    </div>
</div>

<div class="sa-card sa-filter-bar">
    <form method="GET" action="{{ route('superadmin.demo-visits.index') }}">
        <div class="sa-filter-bar__grid">
            <div class="sa-filter-bar__field sa-filter-bar__field--search">
                <label class="sa-filter-bar__label" for="visit-search">Search</label>
                <input
                    id="visit-search"
                    type="search"
                    name="search"
                    value="{{ $filters['search'] ?? '' }}"
                    class="form-control form-control-sm"
                    placeholder="Name, email, company, country"
                >
            </div>
            <div class="sa-filter-bar__field">
                <label class="sa-filter-bar__label" for="visit-persona">Persona</label>
                <select id="visit-persona" name="persona" class="custom-select custom-select-sm">
                    <option value="">All</option>
                    @foreach ($personas as $key => $persona)
                        <option value="{{ $key }}" @selected(($filters['persona'] ?? '') === $key)>{{ $persona['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sa-filter-bar__field">
                <label class="sa-filter-bar__label" for="visit-identity">Identity</label>
                <select id="visit-identity" name="identity" class="custom-select custom-select-sm">
                    <option value="">All</option>
                    <option value="identified" @selected(($filters['identity'] ?? '') === 'identified')>With email</option>
                    <option value="anonymous" @selected(($filters['identity'] ?? '') === 'anonymous')>Anonymous</option>
                </select>
            </div>
            <div class="sa-filter-bar__field">
                <label class="sa-filter-bar__label" for="visit-status">Status</label>
                <select id="visit-status" name="status" class="custom-select custom-select-sm">
                    <option value="">All</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="sa-filter-bar__footer">
            <div></div>
            <div class="sa-filter-bar__submit">
                @if ($hasActiveFilters)
                    <a href="{{ route('superadmin.demo-visits.index') }}" class="btn btn-sm btn-outline-light">Clear</a>
                @endif
                <button type="submit" class="btn btn-sm btn-info">Apply</button>
            </div>
        </div>
    </form>
</div>

<div class="sa-card">
    @if ($visits->isEmpty())
        <div class="sa-empty py-5">
            <div class="sa-empty__icon" aria-hidden="true"><i class="fas fa-user-clock"></i></div>
            <h3 class="sa-empty__title">No demo visits yet</h3>
            <p class="sa-empty__text">When someone starts the live demo, their email and role choice will appear here.</p>
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead>
                <tr>
                    <th>Visitor</th>
                    <th>Country</th>
                    <th>Persona</th>
                    <th>Consent</th>
                    <th>Status</th>
                    <th>Started</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach ($visits as $visit)
                    <tr class="{{ $visit->isNew() ? 'sa-row-highlight' : '' }}">
                        <td>
                            <div class="text-white font-weight-bold">{{ $visit->displayName() }}</div>
                            @if ($visit->email)
                                <div class="sa-muted small">{{ $visit->email }}</div>
                            @else
                                <div class="sa-muted small">No email provided</div>
                            @endif
                            @if ($visit->company)
                                <div class="sa-muted small">{{ $visit->company }}</div>
                            @endif
                        </td>
                        <td class="sa-muted small">{{ $visit->countryLabel() ?: '—' }}</td>
                        <td>
                            <span class="badge badge-info">{{ $visit->personaLabel() }}</span>
                        </td>
                        <td>
                            @if ($visit->isAnonymous())
                                <span class="sa-muted small">—</span>
                            @elseif ($visit->contact_consent)
                                <span class="badge badge-active">Yes</span>
                            @else
                                <span class="sa-muted small">No</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge badge-{{ $visit->isNew() ? 'warning' : ($visit->status === 'closed' ? 'secondary' : 'active') }}">
                                {{ $visit->statusLabel() }}
                            </span>
                        </td>
                        <td class="sa-muted small">{{ $visit->started_at?->diffForHumans() ?? $visit->created_at?->diffForHumans() }}</td>
                        <td class="text-right">
                            <a href="{{ route('superadmin.demo-visits.show', $visit) }}" class="btn btn-sm btn-outline-light">
                                View
                            </a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $visits->links() }}
        </div>
    @endif
</div>
@endsection
