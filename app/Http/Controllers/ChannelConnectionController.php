<?php

namespace App\Http\Controllers;

use App\Enums\Channels\ChannelConnectionStatus;
use App\Enums\Channels\ChannelProvider;
use App\Models\ChannelConnection;
use App\Services\Channels\ChannelConnectionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ChannelConnectionController extends Controller
{
    public function __construct(
        protected ChannelConnectionService $connections,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', ChannelConnection::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'provider' => ['nullable', 'string', Rule::in(array_keys(config('channels.providers', [])))],
            'status' => ['nullable', Rule::in(array_column(ChannelConnectionStatus::cases(), 'value'))],
        ]);

        $channels = ChannelConnection::query()
            ->withCount(['webhookEvents', 'conversations'])
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters) {
                $term = '%'.$filters['search'].'%';
                $query->where(function ($builder) use ($term) {
                    $builder->where('name', 'like', $term)
                        ->orWhere('external_account_id', 'like', $term);
                });
            })
            ->when(filled($filters['provider'] ?? null), fn ($q) => $q->where('provider', $filters['provider']))
            ->when(filled($filters['status'] ?? null), fn ($q) => $q->where('status', $filters['status']))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('channels.index', [
            'channels' => $channels,
            'filters' => $filters,
            'providers' => $this->connections->enabledProviderOptions(),
            'statuses' => collect(ChannelConnectionStatus::cases())
                ->mapWithKeys(fn (ChannelConnectionStatus $status) => [$status->value => $status->label()]),
            'connectionService' => $this->connections,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', ChannelConnection::class);

        return view('channels.create', [
            'providers' => $this->connections->enabledProviderOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', ChannelConnection::class);

        $validated = $request->validate([
            'provider' => ['required', 'string', Rule::in(array_keys($this->connections->enabledProviderOptions()))],
            'name' => ['required', 'string', 'max:255'],
            'external_account_id' => ['nullable', 'string', 'max:255'],
            'external_page_id' => [
                Rule::requiredIf(in_array($request->input('provider'), [
                    ChannelProvider::FacebookLeadAds->value,
                    ChannelProvider::WhatsAppCloud->value,
                ], true)),
                'nullable',
                'string',
                'max:255',
            ],
            'access_token' => [
                Rule::requiredIf(in_array($request->input('provider'), [
                    ChannelProvider::FacebookLeadAds->value,
                    ChannelProvider::WhatsAppCloud->value,
                ], true)),
                'nullable',
                'string',
                'max:5000',
            ],
            'webhook_secret' => ['nullable', 'string', 'max:255'],
            'token_expires_at' => ['nullable', 'date'],
        ]);

        $created = $this->connections->create($request->user()->company, $validated);

        return redirect()
            ->route('channels.show', $created['connection'])
            ->with('success', 'Channel connected. Copy the webhook URL and secret for your integration.')
            ->with('plain_webhook_secret', $created['plain_webhook_secret']);
    }

    public function show(ChannelConnection $channel): View
    {
        $this->authorize('view', $channel);

        $channel->loadCount(['webhookEvents', 'conversations', 'contacts']);

        $recentEvents = $channel->webhookEvents()
            ->latest()
            ->limit(10)
            ->get();

        return view('channels.show', [
            'channel' => $channel,
            'recentEvents' => $recentEvents,
            'health' => $this->connections->healthLabel($channel),
            'tokenExpiringSoon' => $this->connections->isTokenExpiringSoon($channel),
            'webhookUrl' => route('webhooks.channels.inbound', $channel->uuid),
            'hasAdapter' => app(\App\Services\Channels\ChannelManager::class)->has($channel->provider),
        ]);
    }

    public function test(ChannelConnection $channel): RedirectResponse
    {
        $this->authorize('manage', $channel);

        $result = $this->connections->testConnection($channel);

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function sync(ChannelConnection $channel): RedirectResponse
    {
        $this->authorize('manage', $channel);

        $result = $this->connections->syncNow($channel);

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function retry(ChannelConnection $channel): RedirectResponse
    {
        $this->authorize('manage', $channel);

        $this->connections->retry($channel);

        return back()->with('success', 'Retry started. Error counters were reset and a connection test was run.');
    }

    public function disconnect(ChannelConnection $channel): RedirectResponse
    {
        $this->authorize('manage', $channel);

        $this->connections->disconnect($channel);

        return back()->with('success', 'Channel disconnected. Tokens were cleared.');
    }

    public function regenerateSecret(ChannelConnection $channel): RedirectResponse
    {
        $this->authorize('manage', $channel);

        $secret = $this->connections->regenerateWebhookSecret($channel);

        return back()
            ->with('success', 'Webhook secret regenerated. Copy it now — it will not be shown again.')
            ->with('plain_webhook_secret', $secret);
    }

    public function destroy(ChannelConnection $channel): RedirectResponse
    {
        $this->authorize('delete', $channel);

        $channel->delete();

        return redirect()
            ->route('channels.index')
            ->with('success', 'Channel connection removed.');
    }
}
