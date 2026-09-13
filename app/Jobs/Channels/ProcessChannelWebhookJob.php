<?php

namespace App\Jobs\Channels;

use App\Enums\Channels\WebhookEventStatus;
use App\Models\ChannelWebhookEvent;
use App\Services\Channels\ChannelManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessChannelWebhookJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [30, 60, 300, 900];

    public function __construct(
        public int $webhookEventId,
    ) {}

    public function handle(ChannelManager $channels): void
    {
        $event = ChannelWebhookEvent::withoutCompanyScope()->find($this->webhookEventId);

        if (! $event) {
            return;
        }

        if (in_array($event->status, [
            WebhookEventStatus::Processed,
            WebhookEventStatus::Duplicate,
            WebhookEventStatus::Ignored,
        ], true)) {
            return;
        }

        $channels->processEvent($event);
    }

    public function failed(?Throwable $exception): void
    {
        $event = ChannelWebhookEvent::withoutCompanyScope()->find($this->webhookEventId);

        if ($event && $event->status !== WebhookEventStatus::Processed) {
            $event->markFailed($exception?->getMessage() ?: 'Webhook processing failed.');
            $event->connection?->markError($exception?->getMessage() ?: 'Webhook processing failed.');
        }

        Log::error('Channel webhook job failed.', [
            'webhook_event_id' => $this->webhookEventId,
            'error' => $exception?->getMessage(),
        ]);
    }
}
