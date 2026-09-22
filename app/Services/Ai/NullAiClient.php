<?php

namespace App\Services\Ai;

use App\Contracts\Ai\AiClient;
use RuntimeException;

class NullAiClient implements AiClient
{
    public function isAvailable(): bool
    {
        return false;
    }

    public function provider(): string
    {
        return 'null';
    }

    public function complete(string $system, string $user, array $options = []): string
    {
        throw new RuntimeException(
            'AI is not configured. Set AI_ENABLED=true and provide a provider API key, or keep the UI scaffold disabled.'
        );
    }
}
