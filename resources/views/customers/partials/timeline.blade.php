@php
    /** @var \Illuminate\Support\Collection<int, \App\Support\CustomerTimelineEvent> $timeline */
    $timeline = $timeline ?? collect();
@endphp

<div class="card card-outline card-secondary">
    <div class="card-header">
        <h3 class="card-title">Customer timeline</h3>
        <div class="card-tools">
            <span class="badge badge-light">{{ $timeline->count() }}</span>
        </div>
    </div>
    <div class="card-body">
        @if ($timeline->isEmpty())
            <x-empty-state
                class="crm-empty--compact"
                icon="fas fa-stream"
                title="No timeline history yet"
                description="Convert a lead or add related tasks to build this customer’s story."
            />
        @else
            <ul class="customer-timeline">
                @foreach ($timeline as $event)
                    <li class="customer-timeline-item">
                        <span class="customer-timeline-marker bg-{{ $event->color }}" aria-hidden="true">
                            <i class="{{ $event->icon }}"></i>
                        </span>

                        @if ($event->url)
                            <a href="{{ $event->url }}" class="customer-timeline-link">
                                @include('customers.partials.timeline-event-body', ['event' => $event])
                            </a>
                        @else
                            @include('customers.partials.timeline-event-body', ['event' => $event])
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
