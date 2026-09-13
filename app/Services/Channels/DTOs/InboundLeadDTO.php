<?php

namespace App\Services\Channels\DTOs;

use App\Enums\Channels\ChannelProvider;

final class InboundLeadDTO
{
    /**
     * @param  array<string, mixed>  $campaign
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public readonly ChannelProvider $provider,
        public readonly string $name,
        public readonly ?string $email = null,
        public readonly ?string $phone = null,
        public readonly ?string $companyName = null,
        public readonly ?string $notes = null,
        public readonly ?string $externalUserId = null,
        public readonly ?string $externalLeadId = null,
        public readonly array $campaign = [],
        public readonly array $meta = [],
    ) {}

    public function sourceSlug(): string
    {
        return match ($this->provider) {
            ChannelProvider::FacebookLeadAds => 'facebook',
            ChannelProvider::InstagramLeadForms => 'facebook',
            ChannelProvider::WhatsAppCloud => 'whatsapp',
            ChannelProvider::FacebookMessenger => 'facebook',
            ChannelProvider::InstagramDm => 'facebook',
            ChannelProvider::WebsiteForm, ChannelProvider::GenericWebhook, ChannelProvider::PublicApi => 'website',
        };
    }
}
