<?php

namespace App\Services\Channels;

use App\Enums\Channels\ChannelProvider;
use App\Enums\Channels\WebhookEventStatus;
use App\Jobs\Channels\ProcessChannelWebhookJob;
use App\Models\ChannelConnection;
use App\Models\ChannelWebhookEvent;
use App\Models\Company;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WebhookIngestionService
{
    public function __construct(
        protected ChannelManager $channels,
    ) {}

    /**
     * @param  array<string, mixed>  $headers
     */
    public function ingest(
        Company $company,
        ChannelProvider $provider,
        string $payload,
        ?ChannelConnection $connection = null,
        ?string $signature = null,
        ?string $idempotencyKey = null,
        ?string $eventType = null,
        array $headers = [],
        bool $dispatch = true,
    ): ChannelWebhookEvent {
        $idempotencyKey = $idempotencyKey ?: $this->fingerprint($provider, $payload, $connection);

        $existing = ChannelWebhookEvent::withoutCompanyScope()
            ->where('company_id', $company->id)
            ->where('provider', $provider)
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        if ($existing) {
            $existing->markDuplicate();

            return $existing;
        }

        $signatureValid = null;

        if ($this->channels->has($provider)) {
            $signatureValid = $this->channels->adapter($provider)
                ->validateSignature($payload, $signature, $connection);
        }

        $event = DB::transaction(function () use (
            $company,
            $provider,
            $payload,
            $connection,
            $signature,
            $idempotencyKey,
            $eventType,
            $headers,
            $signatureValid,
            $dispatch,
        ) {
            $event = ChannelWebhookEvent::withoutCompanyScope()->create([
                'company_id' => $company->id,
                'channel_connection_id' => $connection?->id,
                'provider' => $provider,
                'event_type' => $eventType,
                'idempotency_key' => $idempotencyKey,
                'status' => $dispatch ? WebhookEventStatus::Queued : WebhookEventStatus::Received,
                'headers' => $headers,
                'payload' => $payload,
                'signature' => $signature,
                'signature_valid' => $signatureValid,
            ]);

            $connection?->markEventReceived();

            ActivityLogger::log('channel.webhook_received', $connection ?? $company, [
                'provider' => $provider->value,
                'webhook_event_id' => $event->id,
                'signature_valid' => $signatureValid,
            ]);

            return $event;
        });

        if ($signatureValid === false) {
            $event->markFailed('Invalid webhook signature.');
            $connection?->markError('Invalid webhook signature.');

            return $event;
        }

        if ($dispatch) {
            ProcessChannelWebhookJob::dispatch($event->id)
                ->onQueue((string) config('channels.webhooks.queue', 'channels'));
        }

        return $event;
    }

    protected function fingerprint(
        ChannelProvider $provider,
        string $payload,
        ?ChannelConnection $connection,
    ): string {
        return hash('sha256', implode('|', [
            $provider->value,
            (string) $connection?->id,
            $payload,
        ]));
    }
}
