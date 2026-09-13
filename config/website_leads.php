<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Website Lead Webhook Secret
    |--------------------------------------------------------------------------
    |
    | Shared secret sent by your website server (never expose in browser JS).
    | Use Authorization: Bearer {secret} or X-Webhook-Secret: {secret}.
    |
    */

    'webhook_secret' => env('WEBSITE_LEAD_WEBHOOK_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Lead Owner
    |--------------------------------------------------------------------------
    |
    | Webhook-created leads require a created_by user. Set an admin email here,
    | or leave null to use the first active admin in the database.
    |
    */

    'created_by_email' => env('WEBSITE_LEAD_CREATED_BY_EMAIL'),

    /*
    |--------------------------------------------------------------------------
    | Rate Limit
    |--------------------------------------------------------------------------
    |
    | Maximum webhook submissions per IP address per minute.
    |
    */

    'rate_limit' => (int) env('WEBSITE_LEAD_RATE_LIMIT', 10),

];
