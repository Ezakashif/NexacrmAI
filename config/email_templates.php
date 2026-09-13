<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default locale for email templates
    |--------------------------------------------------------------------------
    */
    'default_locale' => env('EMAIL_TEMPLATE_LOCALE', 'en'),

    /*
    |--------------------------------------------------------------------------
    | Supported locales (localization-ready)
    |--------------------------------------------------------------------------
    */
    'locales' => [
        'en' => 'English',
        'es' => 'Spanish',
        'fr' => 'French',
        'de' => 'German',
        'pt' => 'Portuguese',
        'ar' => 'Arabic',
        'ur' => 'Urdu',
    ],

    /*
    |--------------------------------------------------------------------------
    | Template categories
    |--------------------------------------------------------------------------
    |
    | Each category defines available placeholder variables and sample data
    | used for preview / test sends.
    |
    */
    'categories' => [

        'welcome' => [
            'label' => 'Welcome',
            'description' => 'Sent after a new workspace admin registers successfully.',
            'placeholders' => [
                'user_name' => 'Recipient display name',
                'user_email' => 'Recipient email',
                'company_name' => 'Company / workspace name',
                'login_url' => 'Login URL',
                'platform_name' => 'Platform name',
                'support_email' => 'Support email address',
            ],
            'sample' => [
                'user_name' => 'Alex Morgan',
                'user_email' => 'alex@example.com',
                'company_name' => 'Acme Corp',
                'login_url' => '/login',
                'platform_name' => 'Algos',
                'support_email' => 'hello@example.com',
            ],
        ],

        'password_reset' => [
            'label' => 'Password Reset',
            'description' => 'Sent when a user requests a password reset link.',
            'placeholders' => [
                'user_name' => 'Recipient display name',
                'user_email' => 'Recipient email',
                'reset_url' => 'Password reset URL',
                'expires_minutes' => 'Link expiry in minutes',
                'platform_name' => 'Platform name',
            ],
            'sample' => [
                'user_name' => 'Alex Morgan',
                'user_email' => 'alex@example.com',
                'reset_url' => '/reset-password/sample-token',
                'expires_minutes' => '60',
                'platform_name' => 'Algos',
            ],
        ],

        'account_activation' => [
            'label' => 'Account Activation',
            'description' => 'Sent to verify / activate a new account email address.',
            'placeholders' => [
                'user_name' => 'Recipient display name',
                'user_email' => 'Recipient email',
                'activation_url' => 'Email verification / activation URL',
                'expires_minutes' => 'Link expiry in minutes',
                'platform_name' => 'Platform name',
            ],
            'sample' => [
                'user_name' => 'Alex Morgan',
                'user_email' => 'alex@example.com',
                'activation_url' => '/verify-email/1/sample-hash',
                'expires_minutes' => '60',
                'platform_name' => 'Algos',
            ],
        ],

        'company_invitation' => [
            'label' => 'Company Invitation',
            'description' => 'Sent when a teammate is invited to join a company workspace.',
            'placeholders' => [
                'invitee_name' => 'Invitee display name',
                'invitee_email' => 'Invitee email',
                'inviter_name' => 'Person who sent the invite',
                'company_name' => 'Company / workspace name',
                'role_names' => 'Assigned role labels',
                'invitation_url' => 'Accept invitation URL',
                'expires_at' => 'Invitation expiry date',
                'platform_name' => 'Platform name',
            ],
            'sample' => [
                'invitee_name' => 'Jordan Lee',
                'invitee_email' => 'jordan@example.com',
                'inviter_name' => 'Alex Morgan',
                'company_name' => 'Acme Corp',
                'role_names' => 'Sales',
                'invitation_url' => '/invitations/sample-token',
                'expires_at' => '2026-08-01',
                'platform_name' => 'Algos',
            ],
        ],

        'user_credentials' => [
            'label' => 'User Credentials',
            'description' => 'Sent when an admin creates a user with a preset password.',
            'placeholders' => [
                'user_name' => 'Recipient display name',
                'user_email' => 'Recipient email / login',
                'temporary_password' => 'Preset temporary password',
                'company_name' => 'Company / workspace name',
                'role_names' => 'Assigned role labels',
                'login_url' => 'Login URL',
                'platform_name' => 'Platform name',
                'support_email' => 'Support email address',
            ],
            'sample' => [
                'user_name' => 'Jordan Lee',
                'user_email' => 'jordan@example.com',
                'temporary_password' => 'TempPass1!',
                'company_name' => 'Acme Corp',
                'role_names' => 'Sales',
                'login_url' => '/login',
                'platform_name' => 'Algos',
                'support_email' => 'hello@example.com',
            ],
        ],

        'lead_assigned' => [
            'label' => 'Lead Assigned',
            'description' => 'Sent when a lead is assigned to a user.',
            'placeholders' => [
                'user_name' => 'Assignee display name',
                'lead_name' => 'Lead name',
                'lead_company' => 'Lead company',
                'lead_email' => 'Lead email',
                'lead_phone' => 'Lead phone',
                'lead_url' => 'CRM lead URL',
                'platform_name' => 'Platform name',
            ],
            'sample' => [
                'user_name' => 'Alex Morgan',
                'lead_name' => 'Sam Rivera',
                'lead_company' => 'Northline',
                'lead_email' => 'sam@northline.test',
                'lead_phone' => '+1 555 0100',
                'lead_url' => '/leads/1',
                'platform_name' => 'Algos',
            ],
        ],

        'task_reminder' => [
            'label' => 'Task Reminder',
            'description' => 'Sent for task due and overdue reminders.',
            'placeholders' => [
                'user_name' => 'Assignee display name',
                'task_title' => 'Task title',
                'due_date' => 'Due date label',
                'tier_label' => 'Due today / Overdue label',
                'task_url' => 'CRM task URL',
                'platform_name' => 'Platform name',
            ],
            'sample' => [
                'user_name' => 'Alex Morgan',
                'task_title' => 'Call Northline prospect',
                'due_date' => 'Jul 24, 2026',
                'tier_label' => 'Due today',
                'task_url' => '/tasks/1',
                'platform_name' => 'Algos',
            ],
        ],

        'trial_ending' => [
            'label' => 'Trial Ending',
            'description' => 'Sent to company owners before a trial expires.',
            'placeholders' => [
                'user_name' => 'Owner display name',
                'company_name' => 'Company / workspace name',
                'trial_ends_at' => 'Trial end date',
                'days_remaining' => 'Days remaining',
                'billing_url' => 'Billing / plans URL',
                'platform_name' => 'Platform name',
            ],
            'sample' => [
                'user_name' => 'Alex Morgan',
                'company_name' => 'Acme Corp',
                'trial_ends_at' => 'Jul 30, 2026',
                'days_remaining' => '3',
                'billing_url' => '/dashboard',
                'platform_name' => 'Algos',
            ],
        ],

        'lead_follow_up' => [
            'label' => 'Lead Follow-Up',
            'description' => 'Sent for lead follow-up due and overdue reminders.',
            'placeholders' => [
                'user_name' => 'Assignee display name',
                'lead_name' => 'Lead name',
                'lead_company' => 'Lead company',
                'lead_email' => 'Lead email',
                'lead_phone' => 'Lead phone',
                'follow_up_date' => 'Follow-up date label',
                'subject_line' => 'Reminder subject',
                'body_line' => 'Reminder body line',
                'lead_url' => 'CRM lead URL',
                'platform_name' => 'Platform name',
            ],
            'sample' => [
                'user_name' => 'Alex Morgan',
                'lead_name' => 'Sam Rivera',
                'lead_company' => 'Northline',
                'lead_email' => 'sam@northline.test',
                'lead_phone' => '+1 555 0100',
                'follow_up_date' => 'Jul 24, 2026',
                'subject_line' => 'Follow-up due today',
                'body_line' => 'You have a lead follow-up that is due today.',
                'lead_url' => '/leads/1',
                'platform_name' => 'Algos',
            ],
        ],

        'user_status_changed' => [
            'label' => 'User Status Changed',
            'description' => 'Sent when an account status is changed by an administrator.',
            'placeholders' => [
                'user_name' => 'Recipient display name',
                'old_status' => 'Previous status',
                'new_status' => 'New status',
                'changed_by' => 'Admin who made the change',
                'platform_name' => 'Platform name',
            ],
            'sample' => [
                'user_name' => 'Alex Morgan',
                'old_status' => 'Active',
                'new_status' => 'Suspended',
                'changed_by' => 'Admin User',
                'platform_name' => 'Algos',
            ],
        ],

        'contact_inquiry' => [
            'label' => 'Contact Inquiry',
            'description' => 'Internal notification for marketing website contact / demo requests.',
            'placeholders' => [
                'inquiry_name' => 'Sender name',
                'inquiry_email' => 'Sender email',
                'inquiry_company' => 'Sender company',
                'inquiry_phone' => 'Sender phone',
                'inquiry_intent' => 'Intent (general / demo)',
                'inquiry_message' => 'Message body',
                'platform_name' => 'Platform name',
            ],
            'sample' => [
                'inquiry_name' => 'Alex Morgan',
                'inquiry_email' => 'alex@example.com',
                'inquiry_company' => 'Northline',
                'inquiry_phone' => '+1 555 0100',
                'inquiry_intent' => 'demo',
                'inquiry_message' => 'We would like a walkthrough.',
                'platform_name' => 'Algos',
            ],
        ],
    ],
];
