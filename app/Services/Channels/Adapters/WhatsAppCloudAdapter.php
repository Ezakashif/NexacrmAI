<?php

namespace App\Services\Channels\Adapters;

use App\Enums\Channels\ChannelProvider;
use App\Models\ChannelConnection;
use App\Models\ChannelWebhookEvent;
use App\Services\Channels\Contracts\ChannelAdapter;
use App\Services\Channels\ConversationService;
use App\Services\Channels\DTOs\ChannelProcessResult;
use App\Services\Channels\DTOs\InboundMessageDTO;
use Illuminate\Support\Arr;

class WhatsAppCloudAdapter implements ChannelAdapter
{
    public function __construct(
        protected ConversationService $conversations,
    ) {}

    public function provider(): ChannelProvider
    {
        return ChannelProvider::WhatsAppCloud;
    }

    public function validateSignature(
        string $payload,
        ?string $signature,
        ?ChannelConnection $connection = null,
    ): bool {
        if (! filled($signature)) {
            return false;
        }

        $secrets = array_values(array_filter([
            config('channels.meta.app_secret'),
            $connection?->webhook_secret,
        ], fn ($secret) => filled($secret)));

        if ($secrets === []) {
            return false;
        }

        foreach ($secrets as $secret) {
            $expected = hash_hmac('sha256', $payload, (string) $secret);

            if (hash_equals('sha256='.$expected, $signature) || hash_equals($expected, $signature)) {
                return true;
            }
        }

        return false;
    }

    public function process(ChannelWebhookEvent $event): ChannelProcessResult
    {
        $connection = $event->connection;

        if ($connection === null) {
            return ChannelProcessResult::ignored('WhatsApp Cloud events require a channel connection.');
        }

        $payload = $event->decodedPayload();
        $messages = $this->extractInboundMessages($payload, $connection);

        if ($messages === []) {
            if ($this->hasStatusesOnly($payload)) {
                return ChannelProcessResult::ignored('WhatsApp status update acknowledged (no inbound message).');
            }

            return ChannelProcessResult::ignored('No WhatsApp inbound messages found in webhook payload.');
        }

        $lastResult = null;

        foreach ($messages as $message) {
            $dto = new InboundMessageDTO(
                provider: $event->provider,
                externalUserId: $message['wa_id'],
                externalThreadId: $message['wa_id'],
                providerMessageId: $message['provider_message_id'],
                body: $message['body'],
                type: $message['type'],
                displayName: $message['display_name'],
                phone: $message['phone'],
                meta: $message['meta'],
            );

            $result = $this->conversations->ingestInboundMessage($dto, $event);
            $lastResult = ChannelProcessResult::message(
                $result['message'],
                $result['conversation'],
                $result['lead'],
                $result['contact'],
            );
        }

        return $lastResult ?? ChannelProcessResult::ignored('No WhatsApp messages were processed.');
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array{
     *     wa_id: string,
     *     phone: string,
     *     display_name: ?string,
     *     provider_message_id: ?string,
     *     body: string,
     *     type: string,
     *     meta: array<string, mixed>
     * }>
     */
    protected function extractInboundMessages(array $payload, ChannelConnection $connection): array
    {
        $messages = [];
        $contactsByWaId = [];

        foreach ($payload['entry'] ?? [] as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            foreach ($entry['changes'] ?? [] as $change) {
                if (! is_array($change) || ($change['field'] ?? '') !== 'messages') {
                    continue;
                }

                $value = is_array($change['value'] ?? null) ? $change['value'] : [];
                $phoneNumberId = (string) Arr::get($value, 'metadata.phone_number_id', '');

                if (
                    filled($connection->external_page_id)
                    && $phoneNumberId !== ''
                    && $phoneNumberId !== (string) $connection->external_page_id
                ) {
                    continue;
                }

                foreach ($value['contacts'] ?? [] as $contact) {
                    if (! is_array($contact)) {
                        continue;
                    }

                    $waId = (string) ($contact['wa_id'] ?? '');

                    if ($waId !== '') {
                        $contactsByWaId[$waId] = is_array($contact['profile'] ?? null)
                            ? ($contact['profile']['name'] ?? null)
                            : null;
                    }
                }

                foreach ($value['messages'] ?? [] as $rawMessage) {
                    if (! is_array($rawMessage)) {
                        continue;
                    }

                    $from = (string) ($rawMessage['from'] ?? '');

                    if ($from === '') {
                        continue;
                    }

                    $type = (string) ($rawMessage['type'] ?? 'text');
                    $body = $this->extractMessageBody($rawMessage, $type);

                    if ($body === '' && $type !== 'text') {
                        $body = '['.$type.']';
                    }

                    if ($body === '') {
                        continue;
                    }

                    $messages[] = [
                        'wa_id' => $from,
                        'phone' => $this->formatPhone($from),
                        'display_name' => filled($contactsByWaId[$from] ?? null)
                            ? (string) $contactsByWaId[$from]
                            : null,
                        'provider_message_id' => filled($rawMessage['id'] ?? null) ? (string) $rawMessage['id'] : null,
                        'body' => $body,
                        'type' => $type === 'text' ? 'text' : $type,
                        'meta' => [
                            'whatsapp' => Arr::only($rawMessage, ['timestamp', 'type', 'context', 'referral']),
                            'metadata' => $value['metadata'] ?? [],
                            'waba_id' => $entry['id'] ?? null,
                        ],
                    ];
                }
            }
        }

        return $messages;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function hasStatusesOnly(array $payload): bool
    {
        $hasStatuses = false;
        $hasMessages = false;

        foreach ($payload['entry'] ?? [] as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            foreach ($entry['changes'] ?? [] as $change) {
                if (! is_array($change)) {
                    continue;
                }

                $value = is_array($change['value'] ?? null) ? $change['value'] : [];

                if (! empty($value['statuses'])) {
                    $hasStatuses = true;
                }

                if (! empty($value['messages'])) {
                    $hasMessages = true;
                }
            }
        }

        return $hasStatuses && ! $hasMessages;
    }

    /**
     * @param  array<string, mixed>  $message
     */
    protected function extractMessageBody(array $message, string $type): string
    {
        return match ($type) {
            'text' => trim((string) Arr::get($message, 'text.body', '')),
            'button' => trim((string) Arr::get($message, 'button.text', Arr::get($message, 'button.payload', ''))),
            'interactive' => trim((string) (
                Arr::get($message, 'interactive.button_reply.title')
                ?? Arr::get($message, 'interactive.list_reply.title')
                ?? ''
            )),
            'image' => trim((string) Arr::get($message, 'image.caption', '[image]')),
            'video' => trim((string) Arr::get($message, 'video.caption', '[video]')),
            'document' => trim((string) (
                Arr::get($message, 'document.filename')
                ?? Arr::get($message, 'document.caption')
                ?? '[document]'
            )),
            'audio' => '[audio]',
            'sticker' => '[sticker]',
            'location' => trim(implode(' ', array_filter([
                Arr::get($message, 'location.name'),
                Arr::get($message, 'location.address'),
                filled(Arr::get($message, 'location.latitude'))
                    ? '('.Arr::get($message, 'location.latitude').', '.Arr::get($message, 'location.longitude').')'
                    : null,
            ]))) ?: '[location]',
            'contacts' => '[contacts]',
            'reaction' => trim((string) Arr::get($message, 'reaction.emoji', '[reaction]')),
            default => filled(Arr::get($message, 'text.body'))
                ? trim((string) Arr::get($message, 'text.body'))
                : '',
        };
    }

    protected function formatPhone(string $waId): string
    {
        $digits = preg_replace('/\D+/', '', $waId) ?: $waId;

        return str_starts_with($digits, '+') ? $digits : '+'.$digits;
    }
}
