<?php

/**
 * First-run / installation setup.
 *
 * Super Admin passwords must never be committed. Leave SETUP_SUPERADMIN_PASSWORD
 * empty in .env.example. After the first Super Admin exists, remove the password
 * from the environment before running `php artisan config:cache`.
 */
return [
    'super_admin' => [
        'name' => env('SETUP_SUPERADMIN_NAME', 'Super Admin'),
        'email' => env('SETUP_SUPERADMIN_EMAIL'),
        'password' => env('SETUP_SUPERADMIN_PASSWORD'),
    ],

    /*
    | Optional fictional demo tenant. Never enable on a buyer production install.
    | Also requires DEMO_SEED_PASSWORD (see config/demo.php).
    */
    'demo_seed' => filter_var(env('DEMO_SEED', false), FILTER_VALIDATE_BOOLEAN),
];
