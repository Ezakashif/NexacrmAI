<?php

namespace App\Services\Channels\DTOs;

use App\Enums\Channels\ChannelProvider;

final class InboundMessageDTO
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public readonly ChannelProvider $provider,
        public readonly string $externalUserId,
        public readonly ?string $externalThreadId = null,
        public readonly ?string $providerMessageId = null,
        public readonly string $body = '',
        public readonly string $type = 'text',
        public readonly ?string $displayName = null,
        public readonly ?string $email = null,
        public readonly ?string $phone = null,
        public readonly array $meta = [],
    ) {}
}
