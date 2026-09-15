<?php

/**
 * Public live-demo tenant identity.
 *
 * Passwords are NEVER stored here. Set DEMO_SEED_PASSWORD in the environment.
 */
return [
    'company_slug' => 'northstar-solutions',
    'company_name' => 'Northstar Solutions',

    // Never commit a real value. Set DEMO_SEED_PASSWORD in the environment.
    'seed_password' => env('DEMO_SEED_PASSWORD'),

    'reset_time' => env('DEMO_RESET_TIME', '03:15'),

    // Must be explicitly enabled. Prevents accidental production resets on deploy.
    'reset_enabled' => (bool) env('DEMO_RESET_ENABLED', false),

    'personas' => [
        'admin' => [
            'email' => 'admin@demo.nexacrm.test',
            'label' => 'Admin Demo',
            'description' => 'Explore the full CRM administration experience.',
        ],
        'sales_manager' => [
            'email' => 'manager@demo.nexacrm.test',
            'label' => 'Sales Manager Demo',
            'description' => 'Manage teams, leads, customers and sales activity.',
        ],
        'sales' => [
            'email' => 'sales@demo.nexacrm.test',
            'label' => 'Sales Representative Demo',
            'description' => 'Work with leads, customers and tasks.',
        ],
    ],
];
