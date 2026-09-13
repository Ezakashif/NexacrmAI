<x-app-layout>
    <x-slot name="header">
        <x-page-header
            :title="$lead->name"
            :subtitle="$lead->company ?? 'No company'"
            :breadcrumbs="[
                ['label' => 'Home', 'url' => route('dashboard')],
                ['label' => 'Leads', 'url' => route('leads.index')],
                ['label' => $lead->name],
            ]"
        >
            <x-slot:actions>
                <div class="mr-1 mb-1">
                    <x-lead-contact-actions :lead="$lead" />
                </div>
                @can('update', $lead)
                    <a href="{{ route('leads.edit', $lead) }}" class="btn btn-default btn-sm mb-1">
                        <i class="fas fa-edit" aria-hidden="true"></i> Edit
                    </a>
                @endcan
                <a href="{{ route('leads.index') }}" class="btn btn-outline-secondary btn-sm mb-1">
                    <i class="fas fa-arrow-left" aria-hidden="true"></i> Back to board
                </a>
            </x-slot:actions>
        </x-page-header>
    </x-slot>

    @php
        $statusBadge = match ($lead->status) {
            'new' => 'primary',
            'contacted' => 'info',
            'qualified' => 'warning',
            'proposal_sent' => 'secondary',
            'won' => 'success',
            'lost' => 'danger',
            default => 'secondary',
        };
    @endphp

    <div class="row">
        <div class="col-lg-4">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Lead details</h3>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Status</dt>
                        <dd class="col-sm-7">
                            <span class="badge badge-{{ $statusBadge }}">{{ $lead->statusLabel() }}</span>
                        </dd>

                        <dt class="col-sm-5">Assigned to</dt>
                        <dd class="col-sm-7">{{ optional($lead->assignee)->name ?? 'Unassigned' }}</dd>

                        <dt class="col-sm-5">Source</dt>
                        <dd class="col-sm-7">
                            {{ $lead->source ? ucfirst(str_replace('_', ' ', $lead->source)) : '—' }}
                        </dd>

                        <dt class="col-sm-5">Estimated value</dt>
                        <dd class="col-sm-7">
                            {{ $lead->estimated_value ? number_format($lead->estimated_value, 2) : '—' }}
                        </dd>

                        <dt class="col-sm-5">Follow-up</dt>
                        <dd class="col-sm-7">
                            @if ($lead->follow_up_date)
                                {{ \Carbon\Carbon::parse($lead->follow_up_date)->format('M j, Y') }}
                            @else
                                —
                            @endif
                        </dd>

                        <dt class="col-sm-5">Email</dt>
                        <dd class="col-sm-7">
                            @if ($lead->email)
                                <div class="d-flex align-items-center flex-wrap">
                                    <span class="mr-2">{{ $lead->email }}</span>
                                    <a href="{{ $lead->emailUrl() }}" class="btn btn-outline-secondary btn-xs" aria-label="Email {{ $lead->name }}">
                                        <i class="fas fa-envelope" aria-hidden="true"></i> Email
                                    </a>
                                </div>
                            @else
                                —
                            @endif
                        </dd>

                        <dt class="col-sm-5">Phone</dt>
                        <dd class="col-sm-7">
                            @if ($lead->phone)
                                <div class="d-flex align-items-center flex-wrap">
                                    <span class="mr-2">{{ $lead->phone }}</span>
                                    <div class="btn-group btn-group-xs">
                                        <a href="{{ $lead->callUrl() }}" class="btn btn-primary btn-xs" aria-label="Call {{ $lead->name }}">
                                            <i class="fas fa-phone" aria-hidden="true"></i> Call
                                        </a>
                                        @if ($lead->whatsAppUrl())
                                            <a href="{{ $lead->whatsAppUrl() }}" target="_blank" rel="noopener" class="btn btn-success btn-xs" aria-label="WhatsApp {{ $lead->name }}">
                                                <i class="fab fa-whatsapp" aria-hidden="true"></i> WhatsApp
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            @else
                                —
                            @endif
                        </dd>
                    </dl>

                    @if ($lead->notes)
                        <hr>
                        <p class="text-muted small mb-1">Initial notes</p>
                        <p class="mb-0">{{ $lead->notes }}</p>
                    @endif
                </div>
                <div class="card-footer">
                    <x-lead-contact-actions :lead="$lead" />
                </div>
            </div>

            @if ($lead->tasks->isNotEmpty())
                <div class="card card-outline card-secondary">
                    <div class="card-header">
                        <h3 class="card-title">Related tasks</h3>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            @foreach ($lead->tasks as $task)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        @can('view', $task)
                                            <a href="{{ route('tasks.show', $task) }}">{{ $task->title }}</a>
                                        @elsecan('update', $task)
                                            <a href="{{ route('tasks.edit', $task) }}">{{ $task->title }}</a>
                                        @else
                                            {{ $task->title }}
                                        @endcan
                                        <div class="small text-muted">
                                            {{ ucfirst(str_replace('_', ' ', $task->status)) }}
                                            @if ($task->due_date)
                                                · Due {{ \Carbon\Carbon::parse($task->due_date)->format('M j, Y') }}
                                            @endif
                                        </div>
                                    </div>
                                    <span class="badge badge-{{ $task->priority === 'urgent' ? 'danger' : ($task->priority === 'high' ? 'warning' : 'secondary') }}">
                                        {{ ucfirst($task->priority) }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            @if ($lead->status !== 'won')
                @can('convert', $lead)
                    <div class="card card-outline card-success">
                        <div class="card-body">
                            <strong class="d-block mb-1">Ready to close?</strong>
                            <p class="text-muted small mb-3">Convert this lead to create a customer and mark it won.</p>
                            <form method="POST" action="{{ route('leads.convert', $lead) }}">
                                @csrf
                                <button
                                    type="submit"
                                    class="btn btn-success btn-block"
                                    data-crm-confirm="Convert this lead to a customer? This marks the lead as won."
                                    data-crm-confirm-title="Convert lead"
                                    data-crm-confirm-label="Convert"
                                    data-crm-confirm-class="btn-success"
                                >
                                    <i class="fas fa-user-check" aria-hidden="true"></i> Convert to customer
                                </button>
                            </form>
                        </div>
                    </div>
                @endcan
            @endif
        </div>

        <div class="col-lg-8">
            @can('createActivity', $lead)
                <div class="card card-outline card-success mb-3">
                    <div class="card-header">
                        <h3 class="card-title">Log activity</h3>
                    </div>
                    <form method="POST" action="{{ route('leads.activities.store', $lead) }}">
                        @csrf
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <x-form-label for="type" :required="true">Activity type</x-form-label>
                                        <select id="type" name="type" class="form-control @error('type') is-invalid @enderror" required>
                                            @foreach ($activityTypes as $value => $label)
                                                <option value="{{ $value }}" @selected(old('type') === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error('type')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <x-form-label for="occurred_at">When</x-form-label>
                                        <input id="occurred_at" name="occurred_at" type="datetime-local"
                                               class="form-control @error('occurred_at') is-invalid @enderror"
                                               value="{{ old('occurred_at', now()->format('Y-m-d\TH:i')) }}">
                                        @error('occurred_at')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <x-form-label for="next_follow_up_date">Next follow-up</x-form-label>
                                        <input id="next_follow_up_date" name="next_follow_up_date" type="date"
                                               class="form-control @error('next_follow_up_date') is-invalid @enderror"
                                               value="{{ old('next_follow_up_date') }}">
                                        @error('next_follow_up_date')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="form-group mb-0">
                                <x-form-label for="summary">What was discussed?</x-form-label>
                                <textarea id="summary" name="summary" rows="3"
                                          class="form-control @error('summary') is-invalid @enderror"
                                          placeholder="Summarize the conversation, outcome, or next steps...">{{ old('summary') }}</textarea>
                                @error('summary')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-plus" aria-hidden="true"></i> Log activity
                            </button>
                        </div>
                    </form>
                </div>
            @endcan

            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title">Activity timeline</h3>
                    <div class="card-tools">
                        <span class="badge badge-light">{{ $lead->activities->count() }}</span>
                    </div>
                </div>
                <div class="card-body">
                    @forelse ($lead->activities as $activity)
                        <div class="crm-activity d-flex mb-3 pb-3 {{ ! $loop->last ? 'border-bottom' : '' }}">
                            <div class="mr-3">
                                <span class="crm-activity__icon btn btn-sm btn-{{ $activity->typeColor() }} disabled" aria-hidden="true">
                                    <i class="{{ $activity->typeIcon() }}"></i>
                                </span>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start flex-wrap">
                                    <strong>{{ $activity->typeLabel() }}</strong>
                                    <small class="text-muted">
                                        {{ $activity->occurred_at->format('M j, Y g:i A') }}
                                    </small>
                                </div>
                                @if ($activity->summary)
                                    <p class="mb-1 mt-1">{{ $activity->summary }}</p>
                                @endif
                                <div class="small text-muted">
                                    @if ($activity->user)
                                        <i class="fas fa-user" aria-hidden="true"></i> {{ $activity->user->name }}
                                    @endif
                                    @if ($activity->next_follow_up_date)
                                        · <i class="fas fa-calendar" aria-hidden="true"></i>
                                        Follow up {{ $activity->next_follow_up_date->format('M j, Y') }}
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <x-empty-state
                            class="crm-empty--compact"
                            icon="fas fa-stream"
                            title="No activities yet"
                            description="Log a call, email, or meeting to build the timeline."
                        />
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
