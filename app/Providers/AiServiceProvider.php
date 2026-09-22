<?php

namespace App\Providers;

use App\Contracts\Ai\AiClient;
use App\Services\Ai\AnthropicClient;
use App\Services\Ai\LeadAssistService;
use App\Services\Ai\NullAiClient;
use App\Services\Ai\OpenAiClient;
use Illuminate\Support\ServiceProvider;

class AiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AiClient::class, function () {
            if (! config('ai.enabled')) {
                return new NullAiClient;
            }

            return match (strtolower((string) config('ai.default_provider', 'null'))) {
                'openai' => new OpenAiClient,
                'anthropic' => new AnthropicClient,
                default => new NullAiClient,
            };
        });

        $this->app->singleton(LeadAssistService::class, function ($app) {
            return new LeadAssistService($app->make(AiClient::class));
        });
    }
}
