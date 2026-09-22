<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI feature flag
    |--------------------------------------------------------------------------
    |
    | When false, the NullAiClient is bound and UI shows a disabled scaffold.
    | Enable only after configuring a provider API key.
    |
    */

    'enabled' => (bool) env('AI_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Default provider
    |--------------------------------------------------------------------------
    |
    | Supported: null, openai, anthropic
    |
    */

    'default_provider' => env('AI_DEFAULT_PROVIDER', 'null'),

    'request_timeout' => (int) env('AI_REQUEST_TIMEOUT', 20),

    'max_output_tokens' => (int) env('AI_MAX_OUTPUT_TOKENS', 800),

    'openai' => [
        'api_key' => env('AI_OPENAI_API_KEY'),
        'base_url' => env('AI_OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'model' => env('AI_OPENAI_MODEL', 'gpt-4o-mini'),
    ],

    'anthropic' => [
        'api_key' => env('AI_ANTHROPIC_API_KEY'),
        'model' => env('AI_ANTHROPIC_MODEL', 'claude-sonnet-4-20250514'),
        'base_url' => env('AI_ANTHROPIC_BASE_URL', 'https://api.anthropic.com'),
        'version' => env('AI_ANTHROPIC_VERSION', '2023-06-01'),
    ],

];
