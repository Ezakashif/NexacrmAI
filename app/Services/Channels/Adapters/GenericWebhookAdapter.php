<?php

namespace App\Services\Channels\Adapters;

use App\Enums\Channels\ChannelProvider;
use App\Models\ChannelConnection;
use App\Models\ChannelWebhookEvent;
use App\Services\Channels\Contracts\ChannelAdapter;
use App\Services\Channels\ConversationService;
use App\Services\Channels\DTOs\ChannelProcessResult;
use App\Services\Channels\DTOs\InboundLeadDTO;
use App\Services\Channels\DTOs\InboundMessageDTO;
use App\Services\Channels\LeadProcessingService;
use Illuminate\Support\Arr;

/**
 * Provider-agnostic JSON webhook adapter used by M1 and future generic integrations.
 *
 * Expected lead payload:
 * {
 *   "type": "lead",
 *   "name": "...",
 *   "email": "...",
 *   "phone": "...",
 *   "company": "...",
 *   "notes": "...",
 *   "external_user_id": "...",
 *   "campaign": { ... }
 * }
 *
 * Expected message payload:
 * {
 *   "type": "message",
 *   "external_user_id": "...",
 *   "body": "...",
 *   "phone": "...",
 *   "email": "..."
 * }
 */
class GenericWebhookAdapter implements ChannelAdapter
{
    public function __construct(
        protected LeadProcessingService $leads,
        protected ConversationService $conversations,
    ) {}

    public function provider(): ChannelProvider
    {
        return ChannelProvider::GenericWebhook;
    }

    public function validateSignature(
        string $payload,
        ?string $signature,
        ?ChannelConnection $connection = null,
    ): bool {
        if ($connection === null || ! filled($connection->webhook_secret)) {
            return $signature === null || $signature === '';
        }

        if (! filled($signature)) {
            return false;
        }

        $expected = hash_hmac('sha256', $payload, (string) $connection->webhook_secret);

        return hash_equals($expected, $signature)
            || hash_equals('sha256='.$expected, $signature);
    }

    public function process(ChannelWebhookEvent $event): ChannelProcessResult
    {
        $payload = $event->decodedPayload();
        $type = (string) ($payload['type'] ?? 'lead');

        if ($type === 'message') {
            return $this->processMessage($event, $payload);
        }

        if ($type !== 'lead') {
            return ChannelProcessResult::ignored("Unsupported generic webhook type [{$type}]");
        }

        $name = trim((string) ($payload['name'] ?? ''));
        $email = filled($payload['email'] ?? null) ? (string) $payload['email'] : null;
        $phone = filled($payload['phone'] ?? null) ? (string) $payload['phone'] : null;

        if ($name === '' || ($email === null && $phone === null)) {
            return ChannelProcessResult::ignored('Lead payload requires name and email or phone.');
        }

        $dto = new InboundLeadDTO(
            provider: $event->provider,
            name: $name,
            email: $email,
            phone: $phone,
            companyName: filled($payload['company'] ?? null) ? (string) $payload['company'] : null,
            notes: filled($payload['notes'] ?? null) ? (string) $payload['notes'] : null,
            externalUserId: filled($payload['external_user_id'] ?? null) ? (string) $payload['external_user_id'] : null,
            externalLeadId: filled($payload['external_lead_id'] ?? null) ? (string) $payload['external_lead_id'] : null,
            campaign: is_array($payload['campaign'] ?? null) ? $payload['campaign'] : [],
            meta: Arr::except($payload, ['type', 'name', 'email', 'phone', 'company', 'notes', 'campaign']),
        );

        $result = $this->leads->process($dto, $event);

        return ChannelProcessResult::lead($result['lead'], $result['contact']);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function processMessage(ChannelWebhookEvent $event, array $payload): ChannelProcessResult
    {
        $externalUserId = trim((string) ($payload['external_user_id'] ?? ''));
        $body = trim((string) ($payload['body'] ?? ''));

        if ($externalUserId === '' || $body === '') {
            return ChannelProcessResult::ignored('Message payload requires external_user_id and body.');
        }

        $messageDto = new InboundMessageDTO(
            provider: $event->provider,
            externalUserId: $externalUserId,
            externalThreadId: filled($payload['external_thread_id'] ?? null) ? (string) $payload['external_thread_id'] : null,
            providerMessageId: filled($payload['provider_message_id'] ?? null) ? (string) $payload['provider_message_id'] : null,
            body: $body,
            type: (string) ($payload['message_type'] ?? 'text'),
            displayName: filled($payload['display_name'] ?? null) ? (string) $payload['display_name'] : null,
            email: filled($payload['email'] ?? null) ? (string) $payload['email'] : null,
            phone: filled($payload['phone'] ?? null) ? (string) $payload['phone'] : null,
            meta: Arr::except($payload, ['type', 'body', 'external_user_id']),
        );

        $result = $this->conversations->ingestInboundMessage($messageDto, $event);

        return ChannelProcessResult::message(
            $result['message'],
            $result['conversation'],
            $result['lead'],
            $result['contact'],
        );
    }
}
