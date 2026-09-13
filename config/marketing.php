<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Brand
    |--------------------------------------------------------------------------
    */

    'name' => env('MARKETING_BRAND_NAME', 'Algos'),
    'tagline' => env('MARKETING_TAGLINE', 'Algo CRM is a simple, affordable CRM for small businesses to manage leads, customers, tasks, and sales in one place. Organize customer information, track leads through your sales pipeline, manage follow-ups, and monitor performance without the complexity of enterprise CRM software.'),
    'description' => env(
        'MARKETING_DESCRIPTION',
        'Algos is a modern multi-tenant CRM for lead management, customer success, tasks, and team collaboration.'
    ),

    /*
    |--------------------------------------------------------------------------
    | Contact & company
    |--------------------------------------------------------------------------
    */

    'contact' => [
        'email' => env('MARKETING_CONTACT_EMAIL', 'hello@algos.test'),
        'phone' => env('MARKETING_CONTACT_PHONE', '+1 (555) 010-2000'),
        // Optional — leave empty/null to hide address everywhere in the public UI.
        'address' => env('MARKETING_CONTACT_ADDRESS') ?: null,
    ],

    'social' => [
        'twitter' => env('MARKETING_SOCIAL_TWITTER', '#'),
        'linkedin' => env('MARKETING_SOCIAL_LINKEDIN', '#'),
        'facebook' => env('MARKETING_SOCIAL_FACEBOOK'),
        'github' => env('MARKETING_SOCIAL_GITHUB', '#'),
    ],

    /*
    |--------------------------------------------------------------------------
    | CTAs
    |--------------------------------------------------------------------------
    */

    'cta' => [
        'trial_route' => 'register',
        'demo_route' => 'marketing.contact',
        'demo_query' => ['intent' => 'demo'],
        'live_demo_route' => 'demo.select',
        'watch_demo_route' => 'marketing.watch-demo',
        'watch_demo_src' => 'marketing/videos/algos-crm-product-demo.mp4',
    ],

    /*
    |--------------------------------------------------------------------------
    | Pricing placeholders (Phase 3E will consume these)
    |--------------------------------------------------------------------------
    */

    'pricing' => [
        'currency' => 'USD',
        'annual_discount_label' => 'Save 20%',
        'headline' => 'Simple pricing for growing teams',
        'subheadline' => 'Start free. Upgrade when your pipeline and team need more power. No credit card required to begin.',
        'trial_note' => 'No credit card required · Cancel anytime during trial',
        'recommended_label' => 'Recommended',
        'plans' => [
            [
                'id' => 'starter',
                'name' => 'Starter',
                'description' => 'For small teams getting organized.',
                'monthly' => 29,
                'annual' => 23,
                'highlighted' => false,
                'cta' => 'Start Free Trial',
                'cta_type' => 'trial',
                'features' => [
                    'Up to 5 users',
                    'Lead & customer management',
                    'Task boards',
                    'Email support',
                ],
            ],
            [
                'id' => 'professional',
                'name' => 'Professional',
                'description' => 'For growing sales teams that need full pipeline visibility.',
                'monthly' => 79,
                'annual' => 63,
                'highlighted' => true,
                'cta' => 'Start Free Trial',
                'cta_type' => 'trial',
                'features' => [
                    'Up to 25 users',
                    'Kanban pipelines',
                    'Reports & analytics',
                    'CSV import / export',
                    'Priority support',
                ],
            ],
            [
                'id' => 'enterprise',
                'name' => 'Enterprise',
                'description' => 'For multi-team organizations that need control at scale.',
                'monthly' => 149,
                'annual' => 119,
                'highlighted' => false,
                'cta' => 'Contact Sales',
                'cta_type' => 'demo',
                'features' => [
                    'Unlimited users',
                    'Role & permission management',
                    'Activity logs',
                    'Dedicated onboarding',
                    'SLA & SSO (coming soon)',
                ],
            ],
        ],
        'comparison' => [
            ['feature' => 'Users', 'starter' => 'Up to 5', 'professional' => 'Up to 25', 'enterprise' => 'Unlimited'],
            ['feature' => 'Lead management', 'starter' => true, 'professional' => true, 'enterprise' => true],
            ['feature' => 'Customer management', 'starter' => true, 'professional' => true, 'enterprise' => true],
            ['feature' => 'Task management', 'starter' => true, 'professional' => true, 'enterprise' => true],
            ['feature' => 'Kanban boards', 'starter' => 'Basic', 'professional' => true, 'enterprise' => true],
            ['feature' => 'Dashboard analytics', 'starter' => 'Core', 'professional' => true, 'enterprise' => true],
            ['feature' => 'Reports', 'starter' => false, 'professional' => true, 'enterprise' => true],
            ['feature' => 'CSV import / export', 'starter' => false, 'professional' => true, 'enterprise' => true],
            ['feature' => 'Global search', 'starter' => true, 'professional' => true, 'enterprise' => true],
            ['feature' => 'Notifications', 'starter' => true, 'professional' => true, 'enterprise' => true],
            ['feature' => 'Role & permissions', 'starter' => 'Basic', 'professional' => true, 'enterprise' => true],
            ['feature' => 'Activity logs', 'starter' => false, 'professional' => true, 'enterprise' => true],
            ['feature' => 'Company management', 'starter' => true, 'professional' => true, 'enterprise' => true],
            ['feature' => 'Multi-tenant architecture', 'starter' => true, 'professional' => true, 'enterprise' => true],
            ['feature' => 'Super Admin tools', 'starter' => false, 'professional' => false, 'enterprise' => true],
            ['feature' => 'Dedicated onboarding', 'starter' => false, 'professional' => false, 'enterprise' => true],
            ['feature' => 'SSO / SLA (coming soon)', 'starter' => false, 'professional' => false, 'enterprise' => 'Roadmap'],
            ['feature' => 'Support', 'starter' => 'Email', 'professional' => 'Priority', 'enterprise' => 'Dedicated'],
        ],
        'faqs' => [
            [
                'id' => 'billing',
                'question' => 'Can I switch between monthly and annual billing?',
                'answer' => 'Yes. Choose monthly or annual when you start. Annual billing reduces the per-user monthly rate.',
            ],
            [
                'id' => 'trial-pricing',
                'question' => 'Do plans include a free trial?',
                'answer' => 'Starter and Professional include a free trial so you can evaluate Algos before paying. Enterprise starts with a guided demo.',
            ],
            [
                'id' => 'limits',
                'question' => 'What happens if we outgrow a plan?',
                'answer' => 'You can move up anytime. User and feature limits expand with Professional and Enterprise.',
            ],
            [
                'id' => 'placeholders',
                'question' => 'Do I need a credit card to start?',
                'answer' => 'No. Start a free trial on Starter or Professional without a credit card. Choose a paid plan when you are ready.',
            ],
            [
                'id' => 'future',
                'question' => 'Will more plans be added?',
                'answer' => 'Possibly. Future tiers—such as industry packs or add-ons—may appear as the product roadmap expands.',
            ],
        ],
        'future_note' => 'Need a custom workspace or volume pricing? Contact sales and we will tailor Enterprise for your team.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Navigation
    |--------------------------------------------------------------------------
    */

    'nav' => [
        ['label' => 'Features', 'route' => 'marketing.features'],
        ['label' => 'Pricing', 'route' => 'marketing.pricing'],
        ['label' => 'About', 'route' => 'marketing.about'],
        ['label' => 'Contact', 'route' => 'marketing.contact'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Home page content
    |--------------------------------------------------------------------------
    */

    'home' => [
        'headline' => 'Run your entire sales pipeline in one CRM your team will actually open every day',
        'subheadline' => 'Algos unifies leads, customers, tasks, kanban boards, and reports so growing teams stop losing follow-ups in spreadsheets and inboxes.',
        'trust_chips' => [
            'Multi-tenant SaaS',
            'Secure authentication',
            'Role-based access',
            'Cloud hosted',
        ],
        'trust_badges' => [
            ['icon' => 'shield', 'label' => 'Secure authentication'],
            ['icon' => 'layers', 'label' => 'Multi-tenant SaaS'],
            ['icon' => 'check-square', 'label' => 'Daily backups'],
            ['icon' => 'users', 'label' => 'Role-based access'],
            ['icon' => 'building', 'label' => 'Cloud hosted'],
            ['icon' => 'scroll-text', 'label' => 'Activity logs'],
            ['icon' => 'bar-chart', 'label' => 'Reports & analytics'],
        ],
        'product_showcase' => [
            'headline' => 'See the CRM in Action',
            'subheadline' => 'Explore the real Algos workspace—dashboards, pipelines, tasks, reports, and admin controls—before you start your trial.',
            'items' => [
                [
                    'id' => 'dashboard',
                    'title' => 'Dashboard',
                    'benefit' => 'See open leads, won deals, and tasks due today the moment your team logs in.',
                    'icon' => 'layout-dashboard',
                    'image' => '/marketing/screenshots/dashboard.PNG',
                ],
                [
                    'id' => 'leads',
                    'title' => 'Leads',
                    'benefit' => 'Capture every inquiry, assign owners, and move deals forward without losing context.',
                    'icon' => 'user-plus',
                    'image' => '/marketing/screenshots/leads.PNG',
                ],
                [
                    'id' => 'kanban',
                    'title' => 'Kanban board',
                    'benefit' => 'Move deals through visible stages so pipeline status is obvious at a glance.',
                    'icon' => 'kanban',
                    'image' => '/marketing/screenshots/leads.PNG',
                ],
                [
                    'id' => 'customers',
                    'title' => 'Customers',
                    'benefit' => 'Keep account history, ownership, and next steps in one place after the deal is won.',
                    'icon' => 'users',
                    'image' => '/marketing/screenshots/customers.PNG',
                ],
                [
                    'id' => 'tasks',
                    'title' => 'Tasks',
                    'benefit' => 'Keep the whole team aligned with deadlines, reminders, and clear ownership on every follow-up.',
                    'icon' => 'check-square',
                    'image' => '/marketing/screenshots/tasks.PNG',
                ],
                [
                    'id' => 'reports',
                    'title' => 'Reports & analytics',
                    'benefit' => 'Turn sales activity into clear reports and pipeline insights your team can act on.',
                    'icon' => 'bar-chart',
                    'image' => '/marketing/screenshots/reports.PNG',
                ],
                [
                    'id' => 'analytics',
                    'title' => 'Dashboard analytics',
                    'benefit' => 'Spot conversion trends and workload pressure before they stall revenue.',
                    'icon' => 'bar-chart',
                    'image' => '/marketing/screenshots/dashboard_analytics.PNG',
                ],
                [
                    'id' => 'activity',
                    'title' => 'Activity logs',
                    'benefit' => 'Audit who changed what—so managers stay informed without interrupting the team.',
                    'icon' => 'scroll-text',
                    'image' => '/marketing/screenshots/activity_log.PNG',
                ],
                [
                    'id' => 'roles',
                    'title' => 'Role and permission management',
                    'benefit' => 'Give every teammate the exact access they need—and nothing they should not see.',
                    'icon' => 'shield',
                    'image' => '/marketing/screenshots/roles_and_permissions.PNG',
                ],
                [
                    'id' => 'company',
                    'title' => 'User management',
                    'benefit' => 'Manage users, roles, and account status from one organized workspace.',
                    'icon' => 'users',
                    'image' => '/marketing/screenshots/user_management.PNG',
                ],
            ],
        ],
        'features' => [
            [
                'icon' => 'user-plus',
                'title' => 'Lead management',
                'problem' => 'Inquiries scatter across inboxes and sheets.',
                'solution' => 'Capture every lead with owners, stages, and history.',
                'benefit' => 'Fewer dropped follow-ups and a pipeline you can trust.',
                'description' => 'Capture every inquiry, score interest, and move deals through a clear pipeline.',
            ],
            [
                'icon' => 'users',
                'title' => 'Customer management',
                'problem' => 'Won deals lose context after handoff.',
                'solution' => 'Keep ownership, history, and notes in one profile.',
                'benefit' => 'Smoother renewals and fewer “who owns this?” moments.',
                'description' => 'Keep ownership, history, and context in one place after the deal is won.',
            ],
            [
                'icon' => 'kanban',
                'title' => 'Kanban boards',
                'problem' => 'Status is buried in lists and status meetings.',
                'solution' => 'Visual boards for leads and tasks everyone can scan.',
                'benefit' => 'Faster decisions and clearer accountability across the team.',
                'description' => 'Visual boards for leads and tasks so progress stays obvious to the whole team.',
            ],
            [
                'icon' => 'bar-chart',
                'title' => 'Reports & analytics',
                'problem' => 'Leaders export CSVs just to answer basic questions.',
                'solution' => 'Live conversion, workload, and pipeline health views.',
                'benefit' => 'Act on what’s converting—without spreadsheet gymnastics.',
                'description' => 'See conversion, workload, and pipeline health without exporting to spreadsheets.',
            ],
            [
                'icon' => 'shield',
                'title' => 'Roles & permissions',
                'problem' => 'Everyone sees everything—or the wrong things.',
                'solution' => 'Company-scoped roles with precise module access.',
                'benefit' => 'Safer data sharing without slowing sales down.',
                'description' => 'Give every teammate the right access with company-scoped roles.',
            ],
            [
                'icon' => 'layers',
                'title' => 'Multi-tenant ready',
                'problem' => 'Shared tools blur company boundaries.',
                'solution' => 'Isolated company data and settings on one platform.',
                'benefit' => 'Scale workspaces confidently as you add teams or tenants.',
                'description' => 'Isolated company data and settings on a shared, scalable platform.',
            ],
        ],
        'how_it_works' => [
            [
                'step' => '01',
                'title' => 'Lead created',
                'description' => 'Capture a new enquiry with the contact details and context your team needs.',
                'icon' => 'user-plus',
            ],
            [
                'step' => '02',
                'title' => 'Assigned to a sales representative',
                'description' => 'Give one owner a clear next action so every opportunity has accountability.',
                'icon' => 'users',
            ],
            [
                'step' => '03',
                'title' => 'Follow-up activities',
                'description' => 'Record calls, notes, and touchpoints while the full conversation stays visible.',
                'icon' => 'scroll-text',
            ],
            [
                'step' => '04',
                'title' => 'Lead converted',
                'description' => 'Move a qualified opportunity forward without losing its sales history.',
                'icon' => 'kanban',
            ],
            [
                'step' => '05',
                'title' => 'Customer management',
                'description' => 'Keep the account, owner, and relationship context together after the deal closes.',
                'icon' => 'users',
            ],
            [
                'step' => '06',
                'title' => 'Tasks & reminders',
                'description' => 'Turn each next step into an assigned task with a clear due date.',
                'icon' => 'check-square',
            ],
            [
                'step' => '07',
                'title' => 'Reports & analytics',
                'description' => 'See pipeline health and team activity, then decide what needs attention next.',
                'icon' => 'bar-chart',
            ],
        ],
        'why_us' => [
            [
                'icon' => 'zap',
                'title' => 'Fast to adopt',
                'description' => 'A clean UI that sales and ops teams understand on day one.',
            ],
            [
                'icon' => 'shield',
                'title' => 'Built for control',
                'description' => 'Permissions, activity logs, and tenant isolation from the start.',
            ],
            [
                'icon' => 'layout-dashboard',
                'title' => 'Clarity over clutter',
                'description' => 'Dashboards and boards that surface what matters—nothing more.',
            ],
        ],
        'small_business_outcomes' => [
            [
                'icon' => 'file-up',
                'title' => 'Leave spreadsheets behind',
                'description' => 'Keep customer records, ownership, and sales context in one shared system.',
            ],
            [
                'icon' => 'check-square',
                'title' => 'Keep every follow-up moving',
                'description' => 'Give every next step an owner and due date before it gets forgotten.',
            ],
            [
                'icon' => 'kanban',
                'title' => 'Organize the sales pipeline',
                'description' => 'Make deal stages visible so the team knows what needs attention now.',
            ],
            [
                'icon' => 'scroll-text',
                'title' => 'Understand team activity',
                'description' => 'Review calls, changes, and progress without chasing updates across tools.',
            ],
            [
                'icon' => 'bar-chart',
                'title' => 'See business performance clearly',
                'description' => 'Use reports to understand what is converting and where work is getting stuck.',
            ],
            [
                'icon' => 'layers',
                'title' => 'Grow without unnecessary complexity',
                'description' => 'Add structure, users, and control as your business grows—without a bloated stack.',
            ],
        ],
        'faqs' => [
            [
                'id' => 'trial',
                'question' => 'Is there a free trial?',
                'answer' => 'Yes. Start a free trial to explore core CRM modules before choosing a plan. No credit card required to begin.',
            ],
            [
                'id' => 'import',
                'question' => 'Can we import existing data?',
                'answer' => 'CSV import and export are available for leads, customers, and users so you can migrate without starting from scratch.',
            ],
            [
                'id' => 'tenants',
                'question' => 'Is Algos multi-tenant?',
                'answer' => 'Yes. Each company gets isolated data, roles, and settings inside a shared platform architecture.',
            ],
            [
                'id' => 'permissions',
                'question' => 'Can we control who sees what?',
                'answer' => 'Role and permission management lets admins grant precise access across leads, customers, tasks, reports, and more.',
            ],
            [
                'id' => 'support',
                'question' => 'What support is included?',
                'answer' => 'Starter includes email support. Professional and Enterprise plans include faster response paths and onboarding options.',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Features page
    |--------------------------------------------------------------------------
    */

    'features_page' => [
        'headline' => 'Every module your revenue team needs',
        'subheadline' => 'Algos covers the full CRM lifecycle—from first lead to closed customer—with clear permissions, reporting, and multi-tenant control.',
        'groups' => [
            [
                'id' => 'sales',
                'title' => 'Sales & relationships',
                'description' => 'Capture demand and keep customer context after the win.',
                'modules' => [
                    [
                        'icon' => 'user-plus',
                        'title' => 'Lead management',
                        'description' => 'Track every inquiry from capture to conversion with owners, stages, and follow-ups.',
                        'highlights' => [
                            'Pipeline stages and ownership',
                            'Activity history on every lead',
                            'Convert leads to customers in one step',
                        ],
                    ],
                    [
                        'icon' => 'users',
                        'title' => 'Customer management',
                        'description' => 'Maintain account records, timelines, and ownership after deals close.',
                        'highlights' => [
                            'Central customer profiles',
                            'Timeline of related activity',
                            'Company-scoped customer data',
                        ],
                    ],
                    [
                        'icon' => 'building',
                        'title' => 'Company management',
                        'description' => 'Organize workspaces, branding, and tenant settings for each company.',
                        'highlights' => [
                            'Per-company configuration',
                            'Plan and limit awareness',
                            'Clean admin controls',
                        ],
                    ],
                ],
            ],
            [
                'id' => 'execution',
                'title' => 'Execution & collaboration',
                'description' => 'Keep the team aligned on what to do next.',
                'modules' => [
                    [
                        'icon' => 'check-square',
                        'title' => 'Task management',
                        'description' => 'Assign follow-ups, track due dates, and close the loop on sales work.',
                        'highlights' => [
                            'Statuses and assignees',
                            'Due-date visibility',
                            'List and detail views',
                        ],
                    ],
                    [
                        'icon' => 'kanban',
                        'title' => 'Kanban boards',
                        'description' => 'Drag-and-drop boards for leads and tasks so progress stays visual.',
                        'highlights' => [
                            'Lead and task boards',
                            'Quick stage updates',
                            'Shared team visibility',
                        ],
                    ],
                    [
                        'icon' => 'bell',
                        'title' => 'Notifications',
                        'description' => 'Stay informed when follow-ups are due and important events happen.',
                        'highlights' => [
                            'In-app notification center',
                            'Mark read / read all',
                            'Actionable reminders',
                        ],
                    ],
                    [
                        'icon' => 'search',
                        'title' => 'Global search',
                        'description' => 'Find leads, customers, tasks, and users instantly across your workspace.',
                        'highlights' => [
                            'Fast workspace search',
                            'Suggestions as you type',
                            'Permission-aware results',
                        ],
                    ],
                ],
            ],
            [
                'id' => 'insights',
                'title' => 'Insights & data',
                'description' => 'Understand performance and move data in or out with confidence.',
                'modules' => [
                    [
                        'icon' => 'layout-dashboard',
                        'title' => 'Dashboard analytics',
                        'description' => 'See pipeline health, workloads, and KPIs the moment you sign in.',
                        'highlights' => [
                            'At-a-glance KPIs',
                            'Pipeline snapshots',
                            'Team-ready overview',
                        ],
                    ],
                    [
                        'icon' => 'bar-chart',
                        'title' => 'Reports',
                        'description' => 'Filter and export the metrics that matter for revenue operations.',
                        'highlights' => [
                            'Filtered reporting views',
                            'Export-ready outputs',
                            'Ops-friendly insights',
                        ],
                    ],
                    [
                        'icon' => 'file-up',
                        'title' => 'CSV import / export',
                        'description' => 'Migrate and extract leads, customers, tasks, and users without friction.',
                        'highlights' => [
                            'Sample CSV templates',
                            'Bulk import flows',
                            'List exports on demand',
                        ],
                    ],
                    [
                        'icon' => 'scroll-text',
                        'title' => 'Activity logs',
                        'description' => 'Audit important changes across the workspace for accountability.',
                        'highlights' => [
                            'Searchable event history',
                            'User and entity context',
                            'Operational transparency',
                        ],
                    ],
                ],
            ],
            [
                'id' => 'platform',
                'title' => 'Platform & administration',
                'description' => 'Security, tenancy, and control built into the product.',
                'modules' => [
                    [
                        'icon' => 'shield',
                        'title' => 'Role & permission management',
                        'description' => 'Grant precise access so every teammate sees only what they should.',
                        'highlights' => [
                            'Custom roles per company',
                            'Granular permissions',
                            'Safe admin workflows',
                        ],
                    ],
                    [
                        'icon' => 'layers',
                        'title' => 'Multi-tenant architecture',
                        'description' => 'Isolate each company’s data and settings on a shared platform.',
                        'highlights' => [
                            'Company-scoped records',
                            'Tenant-safe queries',
                            'Scalable workspace model',
                        ],
                    ],
                    [
                        'icon' => 'crown',
                        'title' => 'Super Admin',
                        'description' => 'Operate the platform across companies with dedicated admin tools.',
                        'highlights' => [
                            'Company oversight',
                            'Platform settings',
                            'Impersonation controls',
                        ],
                    ],
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | About page
    |--------------------------------------------------------------------------
    */

    'about' => [
        'headline' => 'Built for teams who deserve a calmer CRM',
        'subheadline' => 'Algos started with a simple belief: revenue software should feel clear, trustworthy, and fast to adopt—not heavy, noisy, or confusing.',
        'mission' => [
            'title' => 'Mission',
            'body' => 'Help growing teams close more deals with a CRM that stays out of the way—clear pipelines, accountable follow-ups, and data every stakeholder can trust.',
        ],
        'vision' => [
            'title' => 'Vision',
            'body' => 'Become the operating system for modern revenue teams: multi-tenant by design, permission-aware by default, and delightful enough that people actually want to use it every day.',
        ],
        'why' => [
            'title' => 'Why we built Algos',
            'body' => 'Too many CRMs force teams to choose between power and usability. Spreadsheets break under pressure. Enterprise tools drown reps in configuration. We built Algos so sales and ops can share one workspace—leads, customers, tasks, reports, and admin controls—without the clutter.',
            'points' => [
                'Clarity over feature theater',
                'Tenant isolation and permissions from day one',
                'Importable data and exportable truth',
                'A product path from first lead to platform admin',
            ],
        ],
        'timeline' => [
            [
                'year' => '2024',
                'title' => 'Problem framing',
                'description' => 'Interviewed founders and revenue leads stuck between sheets and bloated CRMs.',
            ],
            [
                'year' => '2025',
                'title' => 'Core CRM foundation',
                'description' => 'Shipped multi-tenant leads, customers, tasks, roles, and company isolation.',
            ],
            [
                'year' => '2025',
                'title' => 'Ops & insights',
                'description' => 'Added boards, reporting, CSV flows, search, notifications, and activity logs.',
            ],
            [
                'year' => '2026',
                'title' => 'Public launch path',
                'description' => 'Building the marketing site, packaging plans, and preparing broader onboarding.',
            ],
        ],
        'stack' => [
            ['name' => 'Laravel', 'role' => 'Application framework'],
            ['name' => 'PHP 8.2+', 'role' => 'Backend runtime'],
            ['name' => 'Blade', 'role' => 'Server-rendered UI'],
            ['name' => 'Tailwind CSS', 'role' => 'Design system styling'],
            ['name' => 'Alpine.js', 'role' => 'Light interactivity'],
            ['name' => 'Vite', 'role' => 'Asset pipeline'],
            ['name' => 'SQLite / SQL', 'role' => 'Persistent data'],
            ['name' => 'AdminLTE', 'role' => 'Authenticated CRM shell'],
        ],
    ],

];


