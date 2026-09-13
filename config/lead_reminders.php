<?php

return [
    'enabled' => env('LEAD_FOLLOW_UP_REMINDERS_ENABLED', true),

    /*
    | Daily send time for day_before and due tiers (app timezone).
    */
    'schedule_time' => env('LEAD_FOLLOW_UP_REMINDER_TIME', '08:00'),

    /*
    | Assumed follow-up time of day when only a date is stored.
    | Used to compute the "2 hours before" window.
    */
    'default_follow_up_time' => env('LEAD_FOLLOW_UP_DEFAULT_TIME', '09:00'),

    'tiers' => [
        'day_before' => [
            'enabled' => env('LEAD_FOLLOW_UP_REMINDER_1D_ENABLED', true),
            'label' => '1 day before',
        ],
        'hours_before' => [
            'enabled' => env('LEAD_FOLLOW_UP_REMINDER_2H_ENABLED', true),
            'label' => '2 hours before',
            'hours' => 2,
        ],
        'due' => [
            'enabled' => env('LEAD_FOLLOW_UP_REMINDER_DUE_ENABLED', true),
            'label' => 'Due / overdue',
        ],
        'overdue' => [
            'enabled' => env('LEAD_FOLLOW_UP_REMINDER_OVERDUE_ENABLED', true),
            'label' => 'Overdue daily escalation',
            // Minimum number of whole days between escalation reminders.
            'repeat_days' => (int) env('LEAD_FOLLOW_UP_REMINDER_OVERDUE_REPEAT_DAYS', 1),
        ],
    ],
];
