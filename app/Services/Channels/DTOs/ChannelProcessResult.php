<?php

namespace App\Services\Channels\DTOs;

use App\Models\ChannelContact;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\Lead;

final class ChannelProcessResult
{
    public function __construct(
        public readonly bool $handled,
        public readonly ?Lead $lead = null,
        public readonly ?ChannelContact $contact = null,
        public readonly ?Conversation $conversation = null,
        public readonly ?ConversationMessage $message = null,
        public readonly bool $ignored = false,
        public readonly ?string $reason = null,
    ) {}

    public static function ignored(string $reason): self
    {
        return new self(handled: false, ignored: true, reason: $reason);
    }

    public static function lead(Lead $lead, ?ChannelContact $contact = null): self
    {
        return new self(handled: true, lead: $lead, contact: $contact);
    }

    public static function message(
        ConversationMessage $message,
        Conversation $conversation,
        ?Lead $lead = null,
        ?ChannelContact $contact = null,
    ): self {
        return new self(
            handled: true,
            lead: $lead,
            contact: $contact,
            conversation: $conversation,
            message: $message,
        );
    }
}
