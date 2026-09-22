<?php

namespace App\Services\Ai;

use App\Contracts\Ai\AiClient;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class AnthropicClient implements AiClient
{
    public function isAvailable(): bool
    {
        return filled(config('ai.anthropic.api_key'));
    }

    public function provider(): string
    {
        return 'anthropic';
    }

    public function complete(string $system, string $user, array $options = []): string
    {
        if (! $this->isAvailable()) {
            throw new RuntimeException('Anthropic API key is not configured.');
        }

        $baseUrl = rtrim((string) config('ai.anthropic.base_url'), '/');
        $model = (string) ($options['model'] ?? config('ai.anthropic.model'));
        $maxTokens = (int) ($options['max_tokens'] ?? config('ai.max_output_tokens', 800));

        $response = Http::timeout((int) config('ai.request_timeout', 20))
            ->withHeaders([
                'x-api-key' => (string) config('ai.anthropic.api_key'),
                'anthropic-version' => (string) config('ai.anthropic.version', '2023-06-01'),
            ])
            ->acceptJson()
            ->post($baseUrl.'/v1/messages', [
                'model' => $model,
                'max_tokens' => $maxTokens,
                'system' => $system,
                'messages' => [
                    ['role' => 'user', 'content' => $user],
                ],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Anthropic request failed: HTTP '.$response->status());
        }

        $blocks = data_get($response->json(), 'content', []);
        $text = '';

        if (is_array($blocks)) {
            foreach ($blocks as $block) {
                if (($block['type'] ?? null) === 'text') {
                    $text .= (string) ($block['text'] ?? '');
                }
            }
        }

        if (trim($text) === '') {
            throw new RuntimeException('Anthropic returned an empty completion.');
        }

        return trim($text);
    }
}
