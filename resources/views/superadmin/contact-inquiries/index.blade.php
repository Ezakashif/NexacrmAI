@extends('superadmin.layout')

@section('title', 'Contact inquiries')
@section('heading', 'Contact inquiries')
@section('subheading', 'Demo requests and messages from the marketing website')

@section('content')
@php
    $hasActiveFilters = collect($filters ?? [])
        ->filter(fn ($value) => filled($value))
        ->isNotEmpty();
@endphp

<div class="sa-toolbar">
    <div class="sa-toolbar__meta">
        <span class="sa-toolbar__count">{{ $inquiries->total() }} {{ \Illuminate\Support\Str::plural('inquiry', $inquiries->total()) }}</span>
        @if ($newCount > 0)
            <span class="badge badge-warning">{{ $newCount }} new</span>
        @endif
        @if ($hasActiveFilters)
            <span class="sa-toolbar__hint">Filtered results</span>
        @endif
    </div>
</div>

<div class="sa-card sa-filter-bar">
    <form method="GET" action="{{ route('superadmin.contact-inquiries.index') }}">
        <div class="sa-filter-bar__grid">
            <div class="sa-filter-bar__field sa-filter-bar__field--search">
                <label class="sa-filter-bar__label" for="inquiry-search">Search</label>
                <input
                    id="inquiry-search"
                    type="search"
                    name="search"
                    value="{{ $filters['search'] ?? '' }}"
                    class="form-control form-control-sm"
                    placeholder="Name, email, company, message"
                >
            </div>
            <div class="sa-filter-bar__field">
                <label class="sa-filter-bar__label" for="inquiry-intent">Intent</label>
                <select id="inquiry-intent" name="intent" class="custom-select custom-select-sm">
                    <option value="">All</option>
                    <option value="demo" @selected(($filters['intent'] ?? '') === 'demo')>Demo request</option>
                    <option value="general" @selected(($filters['intent'] ?? '') === 'general')>General</option>
                </select>
            </div>
            <div class="sa-filter-bar__field">
                <label class="sa-filter-bar__label" for="inquiry-status">Status</label>
                <select id="inquiry-status" name="status" class="custom-select custom-select-sm">
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
                    <a href="{{ route('superadmin.contact-inquiries.index') }}" class="btn btn-sm btn-outline-light">Clear</a>
                @endif
                <button type="submit" class="btn btn-sm btn-info">Apply</button>
            </div>
        </div>
    </form>
</div>

<div class="sa-card">
    @if ($inquiries->isEmpty())
        <div class="sa-empty py-5">
            <div class="sa-empty__icon" aria-hidden="true"><i class="fas fa-inbox"></i></div>
            <h3 class="sa-empty__title">No inquiries yet</h3>
            <p class="sa-empty__text">Demo requests and contact messages from the website will appear here.</p>
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead>
                <tr>
                    <th>From</th>
                    <th>Intent</th>
                    <th>Status</th>
                    <th>Message</th>
                    <th>Received</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach ($inquiries as $inquiry)
                    <tr class="{{ $inquiry->isNew() ? 'sa-row-highlight' : '' }}">
                        <td>
                            <div class="text-white font-weight-bold">{{ $inquiry->name }}</div>
                            <div class="sa-muted small">{{ $inquiry->email }}</div>
                            @if ($inquiry->company)
                                <div class="sa-muted small">{{ $inquiry->company }}</div>
                            @endif
                        </td>
                        <td>
                            <span class="badge badge-{{ $inquiry->isDemo() ? 'info' : 'secondary' }}">
                                {{ $inquiry->intentLabel() }}
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-{{ $inquiry->isNew() ? 'warning' : ($inquiry->status === 'closed' ? 'secondary' : 'active') }}">
                                {{ $inquiry->statusLabel() }}
                            </span>
                        </td>
                        <td class="sa-muted" style="max-width: 18rem;">
                            {{ \Illuminate\Support\Str::limit($inquiry->message, 80) }}
                        </td>
                        <td class="sa-muted">{{ $inquiry->created_at?->diffForHumans() }}</td>
                        <td class="text-right">
                            <a href="{{ route('superadmin.contact-inquiries.show', $inquiry) }}" class="btn btn-sm btn-outline-light">
                                View
                            </a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            {{ $inquiries->links() }}
        </div>
    @endif
</div>
@endsection
