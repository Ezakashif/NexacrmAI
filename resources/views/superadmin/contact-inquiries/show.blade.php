@extends('superadmin.layout')

@section('title', $inquiry->name)
@section('heading', $inquiry->isDemo() ? 'Demo request' : 'Contact inquiry')
@section('subheading', $inquiry->name.' · '.$inquiry->created_at?->toDayDateTimeString())

@section('content')
<div class="sa-toolbar">
    <div class="sa-toolbar__meta">
        <a href="{{ route('superadmin.contact-inquiries.index') }}" class="btn btn-sm btn-outline-light">
            <i class="fas fa-arrow-left" aria-hidden="true"></i> Back to inquiries
        </a>
    </div>
    <div class="sa-toolbar__actions">
        <form method="POST" action="{{ route('superadmin.contact-inquiries.status', $inquiry) }}" class="d-flex align-items-center" style="gap: 0.5rem;">
            @csrf
            @method('PATCH')
            <label class="sa-muted small mb-0" for="inquiry-status">Status</label>
            <select id="inquiry-status" name="status" class="custom-select custom-select-sm" onchange="this.form.submit()">
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected($inquiry->status === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </form>
    </div>
</div>

<div class="row">
    <div class="col-lg-4">
        <div class="sa-card">
            <div class="mb-3">
                <span class="badge badge-{{ $inquiry->isDemo() ? 'info' : 'secondary' }}">{{ $inquiry->intentLabel() }}</span>
                <span class="badge badge-{{ $inquiry->isNew() ? 'warning' : ($inquiry->status === 'closed' ? 'secondary' : 'active') }}">
                    {{ $inquiry->statusLabel() }}
                </span>
            </div>

            <dl class="mb-0 small">
                <dt class="sa-muted">Name</dt>
                <dd class="text-white">{{ $inquiry->name }}</dd>
                <dt class="sa-muted">Email</dt>
                <dd class="text-white">
                    <a href="mailto:{{ $inquiry->email }}">{{ $inquiry->email }}</a>
                </dd>
                <dt class="sa-muted">Company</dt>
                <dd class="text-white">{{ $inquiry->company ?: '—' }}</dd>
                <dt class="sa-muted">Phone</dt>
                <dd class="text-white">
                    @if ($inquiry->phone)
                        <a href="tel:{{ preg_replace('/\s+/', '', $inquiry->phone) }}">{{ $inquiry->phone }}</a>
                    @else
                        —
                    @endif
                </dd>
                <dt class="sa-muted">Received</dt>
                <dd class="text-white">{{ $inquiry->created_at?->toDayDateTimeString() }}</dd>
                <dt class="sa-muted">Reviewed</dt>
                <dd class="text-white">
                    @if ($inquiry->reviewed_at)
                        {{ $inquiry->reviewed_at->toDayDateTimeString() }}
                        @if ($inquiry->reviewer)
                            <div class="sa-muted">by {{ $inquiry->reviewer->name }}</div>
                        @endif
                    @else
                        —
                    @endif
                </dd>
            </dl>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="sa-card">
            <h2 class="h5 text-white mb-3">Message</h2>
            <div class="text-white" style="white-space: pre-wrap;">{{ $inquiry->message }}</div>
        </div>

        <div class="sa-card">
            <h2 class="h6 text-white mb-3">Quick actions</h2>
            <div class="d-flex flex-wrap" style="gap: 0.5rem;">
                <a href="mailto:{{ $inquiry->email }}?subject={{ rawurlencode('Re: your '.$inquiry->intentLabel().' — '.config('app.name')) }}" class="btn btn-sm btn-info">
                    <i class="fas fa-reply" aria-hidden="true"></i> Reply by email
                </a>
                @if ($inquiry->status !== 'closed')
                    <form method="POST" action="{{ route('superadmin.contact-inquiries.status', $inquiry) }}">
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
