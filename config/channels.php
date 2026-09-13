<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Enabled channel providers
    |--------------------------------------------------------------------------
    |
    | Keys must match App\Enums\Channels\ChannelProvider values.
    | Adapters are registered in AppServiceProvider / ChannelServiceProvider.
    |
    */
    'providers' => [
        'facebook_lead_ads' => [
            'label' => 'Facebook Lead Ads',
            'category' => 'lead_ads',
            'enabled' => true,
        ],
        'facebook_messenger' => [
            'label' => 'Facebook Messenger',
            'category' => 'messaging',
            'enabled' => true,
        ],
        'instagram_lead_forms' => [
            'label' => 'Instagram Lead Forms',
            'category' => 'lead_ads',
            'enabled' => true,
        ],
        'instagram_dm' => [
            'label' => 'Instagram DM',
            'category' => 'messaging',
            'enabled' => true,
        ],
        'whatsapp_cloud' => [
            'label' => 'WhatsApp Cloud API',
            'category' => 'messaging',
            'enabled' => true,
        ],
        'website_form' => [
            'label' => 'Website Forms',
            'category' => 'forms',
            'enabled' => true,
        ],
        'generic_webhook' => [
            'label' => 'Generic Webhook',
            'category' => 'webhook',
            'enabled' => true,
        ],
        'public_api' => [
            'label' => 'Public API',
            'category' => 'api',
            'enabled' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook processing
    |--------------------------------------------------------------------------
    */
    'webhooks' => [
        'max_attempts' => 5,
        'queue' => env('CHANNELS_WEBHOOK_QUEUE', 'channels'),
        'rate_limit' => (int) env('CHANNELS_WEBHOOK_RATE_LIMIT', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Lead matching
    |--------------------------------------------------------------------------
    */
    'lead_matching' => [
        'match_by_external_id' => true,
        'match_by_email' => true,
        'match_by_phone' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Meta / Facebook
    |--------------------------------------------------------------------------
    */
    'meta' => [
        'app_secret' => env('META_APP_SECRET'),
        'graph_version' => env('META_GRAPH_VERSION', 'v21.0'),
    ],
];
