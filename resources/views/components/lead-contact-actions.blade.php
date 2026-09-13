@props(['lead'])

<div class="btn-group btn-group-sm" role="group" aria-label="Contact actions">
    @if ($lead->callUrl())
        <a href="{{ $lead->callUrl() }}" class="btn btn-primary" aria-label="Call {{ $lead->name }}">
            <i class="fas fa-phone" aria-hidden="true"></i> Call
        </a>
    @else
        <button type="button" class="btn btn-primary" disabled aria-label="Call unavailable — no phone number on file">
            <i class="fas fa-phone" aria-hidden="true"></i> Call
        </button>
    @endif

    @if ($lead->whatsAppUrl())
        <a href="{{ $lead->whatsAppUrl() }}" target="_blank" rel="noopener" class="btn btn-success" aria-label="WhatsApp {{ $lead->name }}">
            <i class="fab fa-whatsapp" aria-hidden="true"></i> WhatsApp
        </a>
    @else
        <button type="button" class="btn btn-success" disabled aria-label="WhatsApp unavailable — no phone number on file">
            <i class="fab fa-whatsapp" aria-hidden="true"></i> WhatsApp
        </button>
    @endif

    @if ($lead->emailUrl())
        <a href="{{ $lead->emailUrl() }}" class="btn btn-outline-secondary" aria-label="Email {{ $lead->name }}">
            <i class="fas fa-envelope" aria-hidden="true"></i> Email
        </a>
    @else
        <button type="button" class="btn btn-outline-secondary" disabled aria-label="Email unavailable — no email address on file">
            <i class="fas fa-envelope" aria-hidden="true"></i> Email
        </button>
    @endif
</div>
