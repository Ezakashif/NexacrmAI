<div class="customer-timeline-body">
    <div class="d-flex justify-content-between align-items-start">
        <div class="pr-2">
            <strong>{{ $event->label }}</strong>
            @if ($event->fromLead)
                <span class="badge badge-light border ml-1">From lead</span>
            @endif
        </div>
        <small class="text-muted text-nowrap">
            {{ $event->occurredAt->format('M j, Y g:i A') }}
        </small>
    </div>

    @if (filled($event->summary))
        <p class="mb-1 mt-1 crm-prewrap">{{ $event->summary }}</p>
    @endif

    @if (filled($event->actorName))
        <div class="small text-muted">
            <i class="fas fa-user" aria-hidden="true"></i> {{ $event->actorName }}
        </div>
    @endif
</div>
