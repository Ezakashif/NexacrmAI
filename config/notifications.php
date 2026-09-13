<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Notification retention
    |--------------------------------------------------------------------------
    |
    | Read database notifications older than this period are pruned by the
    | scheduled notifications:prune command. Unread notifications are retained.
    |
    */
    'retention_days' => env('NOTIFICATION_RETENTION_DAYS', 90),
];
