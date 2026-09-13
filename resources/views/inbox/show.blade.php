<x-app-layout>
    @php
        $contactName = $conversation->contact?->display_name
            ?: $conversation->lead?->name
            ?: $conversation->external_thread_id
            ?: 'Conversation';
        $statusClass = match ($conversation->status) {
            'open' => 'success',
            'pending' => 'warning',
            'closed' => 'secondary',
            default => 'light',
        };
        $canSendWhatsApp = $conversation->provider === \App\Enums\Channels\ChannelProvider::WhatsAppCloud
            && $conversation->status !== 'closed';
    @endphp

    <x-slot name="header">
        <x-page-header
            :title="$contactName"
            :subtitle="$conversation->provider->label()"
            :breadcrumbs="[
                ['label' => 'Home', 'url' => route('dashboard')],
                ['label' => 'Inbox', 'url' => route('inbox.index')],
                ['label' => $contactName],
            ]"
        />
    </x-slot>

    <div class="row">
        <div class="col-lg-4">
            <div class="card card-outline card-primary mb-3">
                <div class="card-body">
                    <div class="mb-3">
                        <span class="badge badge-{{ $statusClass }}">{{ ucfirst($conversation->status) }}</span>
                        @if ($conversation->unread_count > 0)
                            <span class="badge badge-primary">{{ $conversation->unread_count }} unread</span>
                        @endif
                    </div>

                    <dl class="mb-0 small">
                        <dt class="text-muted">Channel</dt>
                        <dd>{{ $conversation->provider->label() }}</dd>
                        <dt class="text-muted">Connection</dt>
                        <dd>{{ $conversation->connection?->name ?: '—' }}</dd>
                        <dt class="text-muted">Phone</dt>
                        <dd>{{ $conversation->contact?->phone ?: $conversation->lead?->phone ?: '—' }}</dd>
                        <dt class="text-muted">Email</dt>
                        <dd>{{ $conversation->contact?->email ?: $conversation->lead?->email ?: '—' }}</dd>
                        <dt class="text-muted">Lead</dt>
                        <dd>
                            @if ($conversation->lead)
                                <a href="{{ route('leads.show', $conversation->lead) }}">{{ $conversation->lead->name }}</a>
                            @else
                                —
                            @endif
                        </dd>
                        <dt class="text-muted">Assignee</dt>
                        <dd>{{ $conversation->assignee?->name ?: 'Unassigned' }}</dd>
                        <dt class="text-muted">Last inbound</dt>
                        <dd>{{ $conversation->last_inbound_at?->toDayDateTimeString() ?? '—' }}</dd>
                    </dl>
                </div>
            </div>

            @if ($canAssign)
                <div class="card mb-3">
                    <div class="card-header"><strong>Assign</strong></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('inbox.assign', $conversation) }}">
                            @csrf
                            <div class="form-group mb-2">
                                <select name="assigned_to" class="form-control form-control-sm">
                                    <option value="">Unassigned</option>
                                    @foreach ($assignableUsers as $user)
                                        <option value="{{ $user->id }}" @selected((int) $conversation->assigned_to === (int) $user->id)>
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="btn btn-sm btn-primary">Save assignee</button>
                        </form>
                    </div>
                </div>
            @endif

            @can('reply', $conversation)
                <div class="card mb-3">
                    <div class="card-header"><strong>Status</strong></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('inbox.status', $conversation) }}" class="d-flex flex-wrap" style="gap: 0.5rem;">
                            @csrf
                            @foreach (['open' => 'Open', 'pending' => 'Pending', 'closed' => 'Closed'] as $value => $label)
                                <button type="submit" name="status" value="{{ $value }}"
                                        class="btn btn-sm {{ $conversation->status === $value ? 'btn-primary' : 'btn-outline-secondary' }}">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </form>
                    </div>
                </div>
            @endcan
        </div>

        <div class="col-lg-8">
            <div class="card card-outline card-secondary mb-3">
                <div class="card-header"><strong>Messages</strong></div>
                <div class="card-body" style="max-height: 520px; overflow-y: auto;">
                    @forelse ($conversation->messages as $message)
                        @php
                            $isOutbound = $message->direction === \App\Models\ConversationMessage::DIRECTION_OUTBOUND;
                        @endphp
                        <div class="d-flex mb-3 {{ $isOutbound ? 'justify-content-end' : 'justify-content-start' }}">
                            <div class="p-2 rounded border {{ $isOutbound ? 'bg-primary text-white' : 'bg-light' }}"
                                 style="max-width: 80%;">
                                <div class="small mb-1 {{ $isOutbound ? 'text-white-50' : 'text-muted' }}">
                                    {{ $isOutbound ? ($message->user?->name ?: 'Agent') : ($contactName) }}
                                    · {{ $message->sent_at?->format('M j, g:i A') ?? $message->created_at?->format('M j, g:i A') }}
                                    · {{ $message->status }}
                                </div>
                                <div style="white-space: pre-wrap;">{{ $message->body }}</div>
                            </div>
                        </div>
                    @empty
                        <x-empty-state
                            class="crm-empty--compact"
                            icon="fas fa-comments"
                            title="No messages yet"
                            description="Waiting for the first inbound message on this thread."
                        />
                    @endforelse
                </div>
            </div>

            @if ($canReply)
                <div class="card">
                    <div class="card-header"><strong>Reply</strong></div>
                    <div class="card-body">
                        @if (! $canSendWhatsApp)
                            <div class="alert alert-warning mb-3">
                                @if ($conversation->status === 'closed')
                                    Reopen this conversation to send a reply.
                                @else
                                    Outbound reply is currently available for WhatsApp Cloud API conversations.
                                @endif
                            </div>
                        @endif

                        <form method="POST" action="{{ route('inbox.reply', $conversation) }}">
                            @csrf
                            <div class="form-group">
                                <label for="body" class="sr-only">Message</label>
                                <textarea id="body" name="body" rows="3"
                                          class="form-control @error('body') is-invalid @enderror"
                                          placeholder="Type your reply..."
                                          {{ $canSendWhatsApp ? '' : 'disabled' }}
                                          required>{{ old('body') }}</textarea>
                                @error('body')<span class="invalid-feedback">{{ $message }}</span>@enderror
                            </div>
                            <button type="submit" class="btn btn-primary" {{ $canSendWhatsApp ? '' : 'disabled' }}>
                                <i class="fab fa-whatsapp" aria-hidden="true"></i> Send WhatsApp reply
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
