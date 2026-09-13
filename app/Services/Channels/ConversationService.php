<?php

namespace App\Services\Channels;

use App\Enums\Channels\ChannelProvider;
use App\Models\ChannelContact;
use App\Models\ChannelWebhookEvent;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\Lead;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\Channels\DTOs\InboundLeadDTO;
use App\Services\Channels\DTOs\InboundMessageDTO;
use App\Services\Channels\Meta\MetaGraphClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class ConversationService
{
    public function __construct(
        protected LeadMatchingService $matcher,
        protected LeadProcessingService $leads,
        protected MetaGraphClient $graph,
    ) {}

    /**
     * @return array{conversation: Conversation, message: ConversationMessage, lead: Lead, contact: ChannelContact}
     */
    public function ingestInboundMessage(InboundMessageDTO $dto, ChannelWebhookEvent $event): array
    {
        return DB::transaction(function () use ($dto, $event) {
            $companyId = (int) $event->company_id;

            if (filled($dto->providerMessageId)) {
                $existingMessage = ConversationMessage::query()
                    ->where('company_id', $companyId)
                    ->where('provider_message_id', $dto->providerMessageId)
                    ->first();

                if ($existingMessage) {
                    $conversation = $existingMessage->conversation()->firstOrFail();
                    $contact = $conversation->contact()->firstOrFail();
                    $lead = $conversation->lead ?: $contact->lead()->first();

                    if (! $lead) {
                        $lead = $this->ensureLeadFromMessage($dto, $event);
                    }

                    return [
                        'conversation' => $conversation,
                        'message' => $existingMessage,
                        'lead' => $lead,
                        'contact' => $contact,
                    ];
                }
            }

            $lead = $this->ensureLeadFromMessage($dto, $event);

            $contact = $this->matcher->upsertContact(
                provider: $dto->provider,
                companyId: $companyId,
                connection: $event->connection,
                lead: $lead,
                externalUserId: $dto->externalUserId,
                email: $dto->email ?? $lead->email,
                phone: $dto->phone ?? $lead->phone,
                displayName: $dto->displayName ?? $lead->name,
            );

            $threadId = $dto->externalThreadId ?: $dto->externalUserId;

            $conversation = Conversation::query()->firstOrNew([
                'company_id' => $companyId,
                'provider' => $dto->provider,
                'external_thread_id' => $threadId,
            ]);

            $conversation->fill([
                'channel_connection_id' => $event->channel_connection_id,
                'channel_contact_id' => $contact->id,
                'lead_id' => $lead->id,
                'status' => $conversation->exists ? $conversation->status : Conversation::STATUS_OPEN,
                'last_message_at' => now(),
                'last_inbound_at' => now(),
                'unread_count' => ($conversation->unread_count ?? 0) + 1,
            ]);
            $conversation->save();

            $message = ConversationMessage::query()->create([
                'company_id' => $companyId,
                'conversation_id' => $conversation->id,
                'channel_contact_id' => $contact->id,
                'direction' => ConversationMessage::DIRECTION_INBOUND,
                'type' => $dto->type,
                'provider_message_id' => $dto->providerMessageId,
                'body' => $dto->body,
                'status' => 'received',
                'sent_at' => now(),
                'meta' => $dto->meta,
            ]);

            ActivityLogger::log('channel.message_received', $conversation, [
                'provider' => $dto->provider->value,
                'message_id' => $message->id,
                'lead_id' => $lead->id,
                'webhook_event_id' => $event->id,
            ]);

            return compact('conversation', 'message', 'lead', 'contact');
        });
    }

    /**
     * @return array{conversation: Conversation, message: ConversationMessage}
     */
    public function reply(Conversation $conversation, User $user, string $body): array
    {
        $body = trim($body);

        if ($body === '') {
            throw new InvalidArgumentException('Reply message cannot be empty.');
        }

        if ($conversation->status === Conversation::STATUS_CLOSED) {
            throw new InvalidArgumentException('This conversation is closed. Reopen it before replying.');
        }

        $conversation->loadMissing(['connection', 'contact']);

        $providerMessageId = null;
        $meta = ['sent_by' => $user->id];

        if ($conversation->provider === ChannelProvider::WhatsAppCloud) {
            $providerMessageId = $this->sendWhatsAppReply($conversation, $body, $meta);
        } else {
            throw new InvalidArgumentException(
                $conversation->provider->label().' outbound replies are not supported yet. WhatsApp Cloud API is available now.'
            );
        }

        return DB::transaction(function () use ($conversation, $user, $body, $providerMessageId, $meta) {
            $message = ConversationMessage::query()->create([
                'company_id' => $conversation->company_id,
                'conversation_id' => $conversation->id,
                'channel_contact_id' => $conversation->channel_contact_id,
                'user_id' => $user->id,
                'direction' => ConversationMessage::DIRECTION_OUTBOUND,
                'type' => 'text',
                'provider_message_id' => $providerMessageId,
                'body' => $body,
                'status' => 'sent',
                'sent_at' => now(),
                'meta' => $meta,
            ]);

            $conversation->forceFill([
                'last_message_at' => now(),
                'unread_count' => 0,
                'status' => $conversation->status === Conversation::STATUS_PENDING
                    ? Conversation::STATUS_OPEN
                    : $conversation->status,
            ])->save();

            ActivityLogger::log('channel.message_sent', $conversation, [
                'provider' => $conversation->provider->value,
                'message_id' => $message->id,
                'user_id' => $user->id,
            ], $user->id);

            return [
                'conversation' => $conversation->fresh(),
                'message' => $message,
            ];
        });
    }

    public function markRead(Conversation $conversation): Conversation
    {
        if ($conversation->unread_count > 0) {
            $conversation->forceFill(['unread_count' => 0])->save();
        }

        return $conversation;
    }

    public function assign(Conversation $conversation, ?User $assignee): Conversation
    {
        if ($assignee !== null && (int) $assignee->company_id !== (int) $conversation->company_id) {
            throw new InvalidArgumentException('Assignee must belong to the same company.');
        }

        $conversation->forceFill([
            'assigned_to' => $assignee?->id,
        ])->save();

        ActivityLogger::log('channel.conversation_assigned', $conversation, [
            'assigned_to' => $assignee?->id,
        ]);

        return $conversation->fresh(['assignee']);
    }

    public function updateStatus(Conversation $conversation, string $status): Conversation
    {
        if (! in_array($status, [
            Conversation::STATUS_OPEN,
            Conversation::STATUS_CLOSED,
            Conversation::STATUS_PENDING,
        ], true)) {
            throw new InvalidArgumentException("Unsupported conversation status [{$status}].");
        }

        $conversation->forceFill(['status' => $status])->save();

        ActivityLogger::log('channel.conversation_status_updated', $conversation, [
            'status' => $status,
        ]);

        return $conversation;
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    protected function sendWhatsAppReply(Conversation $conversation, string $body, array &$meta): string
    {
        $connection = $conversation->connection;
        $contact = $conversation->contact;

        if ($connection === null || ! filled($connection->access_token) || ! filled($connection->external_page_id)) {
            throw new RuntimeException('WhatsApp channel connection is missing a Phone Number ID or access token.');
        }

        $to = $this->whatsAppRecipient($contact, $conversation);

        if ($to === '') {
            throw new RuntimeException('This conversation has no WhatsApp recipient id.');
        }

        $response = $this->graph->sendWhatsAppText(
            (string) $connection->external_page_id,
            (string) $connection->access_token,
            $to,
            $body,
        );

        $meta['graph'] = $response;
        $meta['to'] = $to;

        $messageId = data_get($response, 'messages.0.id');

        if (! filled($messageId)) {
            throw new RuntimeException('WhatsApp accepted the send but did not return a message id.');
        }

        return (string) $messageId;
    }

    protected function whatsAppRecipient(?ChannelContact $contact, Conversation $conversation): string
    {
        $raw = $contact?->external_user_id
            ?: $conversation->external_thread_id
            ?: $contact?->phone
            ?: '';

        return preg_replace('/\D+/', '', (string) $raw) ?: '';
    }

    protected function ensureLeadFromMessage(InboundMessageDTO $dto, ChannelWebhookEvent $event): Lead
    {
        $matched = $this->matcher->matchLead($dto, (int) $event->company_id);

        if ($matched) {
            return $matched;
        }

        $name = $dto->displayName
            ?: ($dto->phone ?: ($dto->email ?: 'Channel contact '.Str::limit($dto->externalUserId, 12, '')));

        $result = $this->leads->process(new InboundLeadDTO(
            provider: $dto->provider,
            name: $name,
            email: $dto->email,
            phone: $dto->phone,
            notes: $dto->body !== '' ? Str::limit($dto->body, 500) : null,
            externalUserId: $dto->externalUserId,
            meta: $dto->meta,
        ), $event);

        return $result['lead'];
    }
}
