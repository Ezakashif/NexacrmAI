<?php

namespace App\Services\Ai;

use App\Contracts\Ai\AiClient;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiClient implements AiClient
{
    public function isAvailable(): bool
    {
        return filled(config('ai.openai.api_key'));
    }

    public function provider(): string
    {
        return 'openai';
    }

    public function complete(string $system, string $user, array $options = []): string
    {
        if (! $this->isAvailable()) {
            throw new RuntimeException('OpenAI API key is not configured.');
        }

        $baseUrl = rtrim((string) config('ai.openai.base_url'), '/');
        $model = (string) ($options['model'] ?? config('ai.openai.model'));
        $maxTokens = (int) ($options['max_tokens'] ?? config('ai.max_output_tokens', 800));

        $response = Http::timeout((int) config('ai.request_timeout', 20))
            ->withToken((string) config('ai.openai.api_key'))
            ->acceptJson()
            ->post($baseUrl.'/chat/completions', [
                'model' => $model,
                'max_tokens' => $maxTokens,
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $user],
                ],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('OpenAI request failed: HTTP '.$response->status());
        }

        $text = data_get($response->json(), 'choices.0.message.content');

        if (! is_string($text) || trim($text) === '') {
            throw new RuntimeException('OpenAI returned an empty completion.');
        }

        return trim($text);
    }
}
