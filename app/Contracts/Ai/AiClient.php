<?php

namespace App\Contracts\Ai;

interface AiClient
{
    /**
     * Whether this client can fulfill requests (enabled + credentials).
     */
    public function isAvailable(): bool;

    /**
     * Provider key: null, openai, anthropic, …
     */
    public function provider(): string;

    /**
     * Complete a text prompt. Returns assistant text or throws on hard failure.
     *
     * @param  array<string, mixed>  $options
     */
    public function complete(string $system, string $user, array $options = []): string;
}
