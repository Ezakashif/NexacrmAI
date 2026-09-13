<?php

return [
    'enabled' => env('TASK_REMINDERS_ENABLED', true),

    /*
    | Daily send time for due and overdue task reminders (app timezone).
    */
    'schedule_time' => env('TASK_REMINDER_TIME', '08:15'),

    'tiers' => [
        'due' => [
            'enabled' => env('TASK_REMINDER_DUE_ENABLED', true),
        ],
        'overdue' => [
            'enabled' => env('TASK_REMINDER_OVERDUE_ENABLED', true),
            // Minimum number of whole days between overdue re-notifications.
            'repeat_days' => (int) env('TASK_REMINDER_OVERDUE_REPEAT_DAYS', 1),
        ],
    ],
];
