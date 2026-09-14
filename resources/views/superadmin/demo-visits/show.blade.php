@extends('superadmin.layout')

@section('title', $visit->displayName())
@section('heading', 'Demo visit')
@section('subheading', $visit->displayName().' · '.($visit->started_at?->toDayDateTimeString() ?? $visit->created_at?->toDayDateTimeString()))

@section('content')
<div class="sa-toolbar">
    <div class="sa-toolbar__meta">
        <a href="{{ route('superadmin.demo-visits.index') }}" class="btn btn-sm btn-outline-light">
            <i class="fas fa-arrow-left" aria-hidden="true"></i> Back to demo visits
        </a>
    </div>
    <div class="sa-toolbar__actions">
        <form method="POST" action="{{ route('superadmin.demo-visits.status', $visit) }}" class="d-flex align-items-center" style="gap: 0.5rem;">
            @csrf
            @method('PATCH')
            <label class="sa-muted small mb-0" for="visit-status">Status</label>
            <select id="visit-status" name="status" class="custom-select custom-select-sm" onchange="this.form.submit()">
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected($visit->status === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </form>
    </div>
</div>

<div class="row">
    <div class="col-lg-5">
        <div class="sa-card">
            <div class="mb-3">
                <span class="badge badge-info">{{ $visit->personaLabel() }}</span>
                <span class="badge badge-{{ $visit->isNew() ? 'warning' : ($visit->status === 'closed' ? 'secondary' : 'active') }}">
                    {{ $visit->statusLabel() }}
                </span>
                @if ($visit->isAnonymous())
                    <span class="badge badge-secondary">Anonymous</span>
                @endif
            </div>

            <dl class="mb-0 small">
                <dt class="sa-muted">Name</dt>
                <dd class="text-white">{{ $visit->name ?: '—' }}</dd>
                <dt class="sa-muted">Email</dt>
                <dd class="text-white">
                    @if ($visit->email)
                        <a href="mailto:{{ $visit->email }}">{{ $visit->email }}</a>
                    @else
                        —
                    @endif
                </dd>
                <dt class="sa-muted">Company</dt>
                <dd class="text-white">{{ $visit->company ?: '—' }}</dd>
                <dt class="sa-muted">Country</dt>
                <dd class="text-white">
                    @if ($visit->countryLabel())
                        {{ $visit->countryLabel() }}
                        @if ($visit->country)
                            <span class="sa-muted">({{ strtoupper($visit->country) }})</span>
                        @endif
                    @else
                        —
                    @endif
                </dd>
                <dt class="sa-muted">Contact consent</dt>
                <dd class="text-white">
                    @if ($visit->isAnonymous())
                        —
                    @else
                        {{ $visit->contact_consent ? 'Yes' : 'No' }}
                    @endif
                </dd>
                <dt class="sa-muted">Started</dt>
                <dd class="text-white">{{ $visit->started_at?->toDayDateTimeString() ?? $visit->created_at?->toDayDateTimeString() }}</dd>
                <dt class="sa-muted">Reviewed</dt>
                <dd class="text-white">
                    @if ($visit->reviewed_at)
                        {{ $visit->reviewed_at->toDayDateTimeString() }}
                        @if ($visit->reviewer)
                            <div class="sa-muted">by {{ $visit->reviewer->name }}</div>
                        @endif
                    @else
                        —
                    @endif
                </dd>
            </dl>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="sa-card">
            <h2 class="h5 text-white mb-3">Session details</h2>
            <dl class="mb-0 small">
                <dt class="sa-muted">Demo persona</dt>
                <dd class="text-white">{{ $visit->personaLabel() }} <span class="sa-muted">({{ $visit->persona }})</span></dd>
                <dt class="sa-muted">IP address</dt>
                <dd class="text-white">{{ $visit->ip_address ?: '—' }}</dd>
                <dt class="sa-muted">User agent</dt>
                <dd class="text-white" style="word-break: break-word;">{{ $visit->user_agent ?: '—' }}</dd>
            </dl>
        </div>

        <div class="sa-card">
            <h2 class="h6 text-white mb-3">Quick actions</h2>
            <div class="d-flex flex-wrap" style="gap: 0.5rem;">
                @if ($visit->email)
                    <a href="mailto:{{ $visit->email }}?subject={{ rawurlencode('Following up on your '.config('app.name').' demo') }}" class="btn btn-sm btn-info">
                        <i class="fas fa-reply" aria-hidden="true"></i> Reply by email
                    </a>
                @endif
                @if ($visit->status !== 'closed')
                    <form method="POST" action="{{ route('superadmin.demo-visits.status', $visit) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="closed">
                        <button type="submit" class="btn btn-sm btn-outline-light">Mark closed</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
