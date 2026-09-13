<?php

use App\Notifications\CustomerCreated;
use App\Notifications\LeadAssigned;
use App\Notifications\LeadFollowUpDue;
use App\Notifications\TaskAssigned;
use App\Notifications\TaskDue;
use App\Notifications\WebsiteLeadReceived;

return [
    'channels' => [
        'database' => 'In-app',
        'email' => 'Email',
    ],

    'types' => [
        'lead_assigned' => [
            'class' => LeadAssigned::class,
            'label' => 'Lead assignments',
            'description' => 'When a lead is assigned to you.',
        ],
        'task_assigned' => [
            'class' => TaskAssigned::class,
            'label' => 'Task assignments',
            'description' => 'When a task is assigned to you.',
        ],
        'task_due' => [
            'class' => TaskDue::class,
            'label' => 'Task due and overdue reminders',
            'description' => 'When an assigned task is due or remains overdue.',
        ],
        'lead_follow_up_due' => [
            'class' => LeadFollowUpDue::class,
            'label' => 'Lead follow-up reminders',
            'description' => 'When an assigned lead follow-up is due or overdue.',
        ],
        'customer_created' => [
            'class' => CustomerCreated::class,
            'label' => 'New customers',
            'description' => 'When a customer is created in your company.',
        ],
        'website_lead_received' => [
            'class' => WebsiteLeadReceived::class,
            'label' => 'Website leads',
            'description' => 'When a website lead is received for your company.',
        ],
    ],
];
