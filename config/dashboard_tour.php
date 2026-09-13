<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Dashboard lifecycle product tour
    |--------------------------------------------------------------------------
    |
    | Steps are shown in order. Missing DOM nodes are skipped at runtime.
    | permission / permission_any gate which steps are offered to the user.
    |
    */

    'steps' => [
        [
            'id' => 'welcome',
            'selector' => '.crm-page-header',
            'title' => 'Welcome to your dashboard',
            'description' => 'This is your daily attention hub. Start here each day to see follow-ups, overdue work, and pipeline health.',
        ],
        [
            'id' => 'quick-actions',
            'selector' => '[data-tour="quick-actions"]',
            'title' => 'Quick create',
            'description' => 'Add a lead, task, or customer without leaving the dashboard. Use these when something new lands in your day.',
        ],
        [
            'id' => 'search',
            'selector' => '#navbar-global-search',
            'title' => 'Global search',
            'description' => 'Find leads, customers, tasks, and users from anywhere in the CRM.',
            'permission' => 'access-search',
        ],
        [
            'id' => 'leads',
            'selector' => '.nav-sidebar a[href$="/leads"]',
            'title' => 'Leads',
            'description' => 'Capture and progress opportunities in list or board view, log activities, schedule follow-ups, and convert won leads.',
            'permission' => 'view.leads',
        ],
        [
            'id' => 'customers',
            'selector' => '.nav-sidebar a[href$="/customers"]',
            'title' => 'Customers',
            'description' => 'Won accounts live here. Keep company details and relationships up to date after conversion.',
            'permission' => 'view.customers',
        ],
        [
            'id' => 'inbox',
            'selector' => '.nav-sidebar a[href$="/inbox"]',
            'title' => 'Inbox',
            'description' => 'Channel conversations (WhatsApp and other connected sources) land here so you can reply without leaving the CRM.',
            'permission' => 'view.inbox',
        ],
        [
            'id' => 'tasks',
            'selector' => '.nav-sidebar a[href$="/tasks"]',
            'title' => 'Tasks',
            'description' => 'Your work queue and board. Assign owners, set due dates, and clear overdue items to keep deals moving.',
            'permission' => 'view.tasks',
        ],
        [
            'id' => 'attention',
            'selector' => '.crm-attention',
            'title' => 'Today’s attention strip',
            'description' => 'KPIs for follow-ups today, pending and overdue tasks, and conversion rate—jump straight into the work that matters.',
            'permission_any' => ['view.leads', 'view.tasks'],
        ],
        [
            'id' => 'follow-ups',
            'selector' => '#todays-follow-ups',
            'title' => 'Follow-ups today',
            'description' => 'Leads with a follow-up due today. Open a lead to log the call or move its stage.',
            'permission' => 'view.leads',
        ],
        [
            'id' => 'overdue-tasks',
            'selector' => '#overdue-tasks',
            'title' => 'Overdue tasks',
            'description' => 'Work past its due date. Clear these first to unblock pipeline momentum.',
            'permission' => 'view.tasks',
        ],
        [
            'id' => 'pipeline',
            'selector' => '[data-tour="lead-pipeline"]',
            'title' => 'Lead pipeline snapshot',
            'description' => 'Counts of new, won, and lost leads. Click a card to open the filtered pipeline.',
            'permission' => 'view.leads',
        ],
        [
            'id' => 'totals',
            'selector' => '[data-tour="workspace-totals"]',
            'title' => 'Workspace totals',
            'description' => 'High-level counts for customers, leads, and tasks in your company workspace.',
            'permission_any' => ['view.customers', 'view.leads', 'view.tasks'],
        ],
        [
            'id' => 'charts',
            'selector' => '[data-tour="dashboard-charts"]',
            'title' => 'Trend charts',
            'description' => 'Lead source mix (and monthly growth below) help you see where pipeline is coming from over time.',
            'permission' => 'view.leads',
        ],
        [
            'id' => 'reports',
            'selector' => '.nav-sidebar a[href$="/reports"]',
            'title' => 'Reports',
            'description' => 'Deeper analytics and exports when you need more than the dashboard snapshot.',
            'permission' => 'access-reports',
        ],
        [
            'id' => 'notifications',
            'selector' => '.nav-sidebar a[href$="/notifications"]',
            'title' => 'Notifications',
            'description' => 'In-app alerts for reminders, assignments, and important workspace updates.',
            'permission' => 'view.notifications',
        ],
        [
            'id' => 'docs',
            'selector' => '.nav-sidebar a[href*="/docs"]',
            'title' => 'Documentation',
            'description' => 'Open the in-app user manual anytime you need a deeper walkthrough of a module.',
        ],
        [
            'id' => 'replay',
            'selector' => '[data-tour="tour-replay"]',
            'title' => 'Replay anytime',
            'description' => 'You can restart this tour from Replay tour on the dashboard whenever you want a refresher.',
        ],
    ],

];
