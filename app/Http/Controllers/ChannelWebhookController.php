<?php

namespace App\Http\Controllers;

use App\Enums\Channels\ChannelProvider;
use App\Models\ChannelConnection;
use App\Services\Channels\WebhookIngestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ChannelWebhookController extends Controller
{
    public function __construct(
        protected WebhookIngestionService $ingestion,
    ) {}

    public function inbound(Request $request, string $uuid): JsonResponse|Response
    {
        $connection = ChannelConnection::withoutCompanyScope()
            ->where('uuid', $uuid)
            ->first();

        if (! $connection) {
            return response()->json(['message' => 'Unknown channel connection.'], 404);
        }

        if ($request->isMethod('GET') && $this->usesMetaVerification($connection->provider)) {
            return $this->verifyMetaSubscription($request, $connection);
        }

        $company = $connection->company()->first();

        if (! $company) {
            return response()->json(['message' => 'Channel company is unavailable.'], 404);
        }

        $payload = $request->getContent() ?: '{}';

        $signature = $request->header('X-Channel-Signature')
            ?? $request->header('X-Hub-Signature-256')
            ?? $request->header('X-Signature');

        $event = $this->ingestion->ingest(
            company: $company,
            provider: $connection->provider,
            payload: $payload,
            connection: $connection,
            signature: $signature,
            idempotencyKey: $this->resolveIdempotencyKey($connection->provider, $payload),
            eventType: $this->resolveEventType($connection->provider, $payload, $request),
            headers: [
                'content_type' => $request->header('Content-Type'),
                'user_agent' => $request->userAgent(),
            ],
        );

        if ($event->signature_valid === false) {
            return response()->json([
                'message' => 'Invalid signature.',
                'event_id' => $event->uuid,
            ], 401);
        }

        return response()->json([
            'message' => 'accepted',
            'event_id' => $event->uuid,
            'status' => $event->status->value,
        ], 202);
    }

    protected function usesMetaVerification(ChannelProvider $provider): bool
    {
        return in_array($provider, [
            ChannelProvider::FacebookLeadAds,
            ChannelProvider::WhatsAppCloud,
        ], true);
    }

    protected function verifyMetaSubscription(Request $request, ChannelConnection $connection): Response
    {
        $mode = (string) ($request->query('hub_mode') ?? $request->query('hub.mode') ?? '');
        $token = (string) ($request->query('hub_verify_token') ?? $request->query('hub.verify_token') ?? '');
        $challenge = (string) ($request->query('hub_challenge') ?? $request->query('hub.challenge') ?? '');

        if (
            $mode === 'subscribe'
            && filled($token)
            && filled($challenge)
            && filled($connection->verify_token)
            && hash_equals((string) $connection->verify_token, $token)
        ) {
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        return response('Forbidden', 403);
    }

    protected function resolveIdempotencyKey(ChannelProvider $provider, string $payload): ?string
    {
        $data = json_decode($payload, true);

        if (! is_array($data)) {
            return null;
        }

        if ($provider === ChannelProvider::FacebookLeadAds) {
            foreach ($data['entry'] ?? [] as $entry) {
                if (! is_array($entry)) {
                    continue;
                }

                foreach ($entry['changes'] ?? [] as $change) {
                    if (! is_array($change) || ($change['field'] ?? '') !== 'leadgen') {
                        continue;
                    }

                    $leadgenId = $change['value']['leadgen_id'] ?? null;

                    if (filled($leadgenId)) {
                        return 'facebook_leadgen_'.(string) $leadgenId;
                    }
                }
            }
        }

        if ($provider === ChannelProvider::WhatsAppCloud) {
            foreach ($data['entry'] ?? [] as $entry) {
                if (! is_array($entry)) {
                    continue;
                }

                foreach ($entry['changes'] ?? [] as $change) {
                    if (! is_array($change) || ($change['field'] ?? '') !== 'messages') {
                        continue;
                    }

                    $value = is_array($change['value'] ?? null) ? $change['value'] : [];

                    foreach ($value['messages'] ?? [] as $message) {
                        if (is_array($message) && filled($message['id'] ?? null)) {
                            return 'whatsapp_message_'.(string) $message['id'];
                        }
                    }

                    foreach ($value['statuses'] ?? [] as $status) {
                        if (is_array($status) && filled($status['id'] ?? null) && filled($status['status'] ?? null)) {
                            return 'whatsapp_status_'.(string) $status['id'].'_'.(string) $status['status'];
                        }
                    }
                }
            }
        }

        return null;
    }

    protected function resolveEventType(ChannelProvider $provider, string $payload, Request $request): string
    {
        if ($provider === ChannelProvider::WhatsAppCloud) {
            $data = json_decode($payload, true);

            if (! is_array($data)) {
                return 'whatsapp';
            }

            foreach ($data['entry'] ?? [] as $entry) {
                if (! is_array($entry)) {
                    continue;
                }

                foreach ($entry['changes'] ?? [] as $change) {
                    if (! is_array($change)) {
                        continue;
                    }

                    $value = is_array($change['value'] ?? null) ? $change['value'] : [];

                    if (! empty($value['messages'])) {
                        return 'message';
                    }

                    if (! empty($value['statuses'])) {
                        return 'status';
                    }
                }
            }

            return (string) ($data['object'] ?? 'whatsapp');
        }

        if ($provider !== ChannelProvider::FacebookLeadAds) {
            return $request->header('X-Channel-Event', 'inbound');
        }

        $data = json_decode($payload, true);

        if (! is_array($data)) {
            return 'leadgen';
        }

        foreach ($data['entry'] ?? [] as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            foreach ($entry['changes'] ?? [] as $change) {
                if (is_array($change) && ($change['field'] ?? '') === 'leadgen') {
                    return 'leadgen';
                }
            }
        }

        return (string) ($data['object'] ?? 'meta_webhook');
    }
}
