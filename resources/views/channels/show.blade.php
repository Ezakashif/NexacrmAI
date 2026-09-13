<x-app-layout>
    <x-slot name="header">
        <x-page-header
            title="{{ $channel->name }}"
            :subtitle="$channel->provider->label()"
            :breadcrumbs="[
                ['label' => 'Home', 'url' => route('dashboard')],
                ['label' => 'Channels', 'url' => route('channels.index')],
                ['label' => $channel->name],
            ]"
        >
            <x-slot:actions>
                @can('manage', $channel)
                    <form method="POST" action="{{ route('channels.test', $channel) }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-vial" aria-hidden="true"></i> Test connection
                        </button>
                    </form>
                    <form method="POST" action="{{ route('channels.sync', $channel) }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-default btn-sm">
                            <i class="fas fa-sync" aria-hidden="true"></i> Sync now
                        </button>
                    </form>
                @endcan
            </x-slot:actions>
        </x-page-header>
    </x-slot>

    @if (session('plain_webhook_secret'))
        <div class="alert alert-warning crm-keep-alert">
            <strong>New webhook secret</strong> (copy now):
            <code class="d-block mt-1 user-select-all">{{ session('plain_webhook_secret') }}</code>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-4">
            <div class="card card-outline card-primary">
                <div class="card-body">
                    @php
                        $statusClass = match ($channel->status->value) {
                            'connected' => 'success',
                            'pending' => 'info',
                            'token_expiring' => 'warning',
                            'error' => 'danger',
                            default => 'secondary',
                        };
                        $healthClass = match ($health) {
                            'Healthy' => 'success',
                            'Warning' => 'warning',
                            'Error' => 'danger',
                            'Pending' => 'info',
                            default => 'secondary',
                        };
                    @endphp

                    <div class="mb-3">
                        <span class="badge badge-{{ $statusClass }}">{{ $channel->status->label() }}</span>
                        <span class="badge badge-{{ $healthClass }}">{{ $health }}</span>
                        @if ($tokenExpiringSoon)
                            <span class="badge badge-warning">Token expiring</span>
                        @endif
                        @if (! $hasAdapter)
                            <span class="badge badge-light">Adapter coming soon</span>
                        @endif
                    </div>

                    <dl class="mb-0 small">
                        <dt class="text-muted">Provider</dt>
                        <dd>{{ $channel->provider->label() }}</dd>
                        <dt class="text-muted">External account</dt>
                        <dd>{{ $channel->external_account_id ?: '—' }}</dd>
                        @if ($channel->provider === \App\Enums\Channels\ChannelProvider::FacebookLeadAds)
                            <dt class="text-muted">Facebook Page ID</dt>
                            <dd>{{ $channel->external_page_id ?: '—' }}</dd>
                            <dt class="text-muted">Verify token</dt>
                            <dd><code class="user-select-all">{{ $channel->verify_token ?: '—' }}</code></dd>
                        @elseif ($channel->provider === \App\Enums\Channels\ChannelProvider::WhatsAppCloud)
                            <dt class="text-muted">Phone Number ID</dt>
                            <dd>{{ $channel->external_page_id ?: '—' }}</dd>
                            <dt class="text-muted">WABA ID</dt>
                            <dd>{{ $channel->external_account_id ?: '—' }}</dd>
                            <dt class="text-muted">Verify token</dt>
                            <dd><code class="user-select-all">{{ $channel->verify_token ?: '—' }}</code></dd>
                        @endif
                        <dt class="text-muted">Token expiry</dt>
                        <dd>{{ $channel->token_expires_at?->toDayDateTimeString() ?? '—' }}</dd>
                        <dt class="text-muted">Last sync</dt>
                        <dd>{{ $channel->last_sync_at?->toDayDateTimeString() ?? '—' }}</dd>
                        <dt class="text-muted">Last event</dt>
                        <dd>{{ $channel->last_event_at?->toDayDateTimeString() ?? '—' }}</dd>
                        <dt class="text-muted">Error count</dt>
                        <dd>{{ $channel->error_count }}</dd>
                        <dt class="text-muted">Last error</dt>
                        <dd>{{ $channel->last_error_message ?: '—' }}</dd>
                        <dt class="text-muted">Events / conversations / contacts</dt>
                        <dd>
                            {{ $channel->webhook_events_count }}
                            /
                            {{ $channel->conversations_count }}
                            /
                            {{ $channel->contacts_count }}
                        </dd>
                    </dl>
                </div>
                @can('manage', $channel)
                    <div class="card-footer">
                        <div class="d-flex flex-wrap" style="gap: 0.5rem;">
                            @if ($channel->status->value === 'error')
                                <form method="POST" action="{{ route('channels.retry', $channel) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-warning">
                                        <i class="fas fa-redo" aria-hidden="true"></i> Retry
                                    </button>
                                </form>
                            @endif
                            @if ($channel->status->value !== 'disconnected')
                                <form method="POST" action="{{ route('channels.disconnect', $channel) }}"
                                      data-confirm="Disconnect this channel and clear tokens?">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">Disconnect</button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('channels.destroy', $channel) }}"
                                  data-confirm="Delete this channel connection permanently?">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </div>
                    </div>
                @endcan
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card card-outline card-secondary mb-3">
                <div class="card-header">
                    <strong>Webhook endpoint</strong>
                </div>
                <div class="card-body">
                    @if ($channel->provider === \App\Enums\Channels\ChannelProvider::FacebookLeadAds)
                        <p class="text-muted small mb-2">
                            In Meta App Dashboard → Webhooks, subscribe this callback URL to <strong>leadgen</strong> for your Page.
                            Use the verify token shown in the connection details. Meta signs POSTs with
                            <code>X-Hub-Signature-256</code> using your app secret (<code>META_APP_SECRET</code>).
                        </p>
                    @elseif ($channel->provider === \App\Enums\Channels\ChannelProvider::WhatsAppCloud)
                        <p class="text-muted small mb-2">
                            In Meta App Dashboard → WhatsApp → Configuration → Webhook, set this callback URL.
                            Subscribe to <strong>messages</strong>. Use the verify token shown in the connection details.
                            Meta signs POSTs with <code>X-Hub-Signature-256</code> using <code>META_APP_SECRET</code>.
                        </p>
                    @else
                        <p class="text-muted small mb-2">
                            Send signed JSON POSTs here. Header: <code>X-Channel-Signature: sha256=&lt;hmac&gt;</code>
                            using the webhook secret.
                        </p>
                    @endif
                    <label class="small text-muted mb-1">Webhook URL</label>
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" readonly value="{{ $webhookUrl }}" id="channel-webhook-url">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-default" onclick="navigator.clipboard.writeText(document.getElementById('channel-webhook-url').value)">Copy</button>
                        </div>
                    </div>

                    @if ($channel->provider === \App\Enums\Channels\ChannelProvider::FacebookLeadAds)
                        <p class="small text-muted mb-2">
                            Meta sends <code>leadgen</code> notifications here. The CRM fetches lead field data from the Graph API using your page access token.
                        </p>
                    @elseif ($channel->provider === \App\Enums\Channels\ChannelProvider::WhatsAppCloud)
                        <p class="small text-muted mb-2">
                            Inbound WhatsApp messages create/update a lead (source <code>whatsapp</code>) and open a conversation thread.
                            Delivery status webhooks are acknowledged but ignored for now.
                        </p>
                    @else
                        <p class="small text-muted mb-2">
                            Example lead payload:
                        </p>
                        <pre class="bg-light border rounded p-2 small mb-3">{"type":"lead","name":"Alex Morgan","email":"alex@example.com","phone":"+15550100000","company":"Northline","notes":"From website"}</pre>
                    @endif

                    @can('manage', $channel)
                        <form method="POST" action="{{ route('channels.regenerate-secret', $channel) }}"
                              data-confirm="Regenerate webhook secret? Existing integrations will stop working until updated.">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-primary">
                                Regenerate webhook secret
                            </button>
                        </form>
                        <small class="form-text text-muted mt-2">
                            The secret is stored encrypted. For security it is only shown when generated or regenerated.
                        </small>
                    @endcan
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <strong>Recent webhook events</strong>
                </div>
                @if ($recentEvents->isEmpty())
                    <div class="card-body">
                        <x-empty-state
                            class="crm-empty--compact"
                            icon="fas fa-satellite-dish"
                            title="No events yet"
                            description="Use Test connection, or POST a signed payload to the webhook URL."
                        />
                    </div>
                @else
                    <div class="card-body table-responsive p-0">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>When</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Signature</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($recentEvents as $event)
                                    <tr>
                                        <td class="text-muted">{{ $event->created_at?->diffForHumans() }}</td>
                                        <td>{{ $event->event_type ?: '—' }}</td>
                                        <td><span class="badge badge-light">{{ $event->status->value }}</span></td>
                                        <td>
                                            @if ($event->signature_valid === true)
                                                <span class="text-success">valid</span>
                                            @elseif ($event->signature_valid === false)
                                                <span class="text-danger">invalid</span>
                                            @else
                                                <span class="text-muted">n/a</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
