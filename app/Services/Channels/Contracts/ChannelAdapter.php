<?php

namespace App\Services\Channels\Contracts;

use App\Enums\Channels\ChannelProvider;
use App\Models\ChannelConnection;
use App\Models\ChannelWebhookEvent;
use App\Services\Channels\DTOs\ChannelProcessResult;

interface ChannelAdapter
{
    public function provider(): ChannelProvider;

    /**
     * Validate provider-specific signature/authenticity for a raw payload.
     * Connection may be null for generic providers that use shared secrets.
     */
    public function validateSignature(
        string $payload,
        ?string $signature,
        ?ChannelConnection $connection = null,
    ): bool;

    /**
     * Parse and process a stored webhook event into CRM side-effects.
     */
    public function process(ChannelWebhookEvent $event): ChannelProcessResult;
}
