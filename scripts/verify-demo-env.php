<?php

use App\Models\Company;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\Scopes\CompanyScope;
use App\Models\Task;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\ReportService;
use App\Support\CurrentCompany;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Support\Facades\Hash;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$company = Company::query()->where('slug', DemoDataSeeder::COMPANY_SLUG)->firstOrFail();
app(CurrentCompany::class)->set($company);

$out = [
    'company' => $company->only(['id', 'name', 'slug', 'status', 'subscription_status', 'plan_id']),
    'counts' => [
        'users' => User::withoutGlobalScope(CompanyScope::class)->where('company_id', $company->id)->count(),
        'leads' => Lead::withoutGlobalScope(CompanyScope::class)->where('company_id', $company->id)->count(),
        'customers' => Customer::withoutGlobalScope(CompanyScope::class)->where('company_id', $company->id)->count(),
        'tasks' => Task::withoutGlobalScope(CompanyScope::class)->where('company_id', $company->id)->count(),
        'activities' => LeadActivity::withoutGlobalScope(CompanyScope::class)->where('company_id', $company->id)->count(),
        'lead_statuses' => Lead::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $company->id)
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status'),
        'task_statuses' => Task::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $company->id)
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status'),
    ],
    'users' => [],
];

foreach (DemoDataSeeder::USERS as $key => $definition) {
    $user = User::withoutGlobalScope(CompanyScope::class)
        ->where('email', $definition['email'])
        ->firstOrFail();

    $passwordOk = Hash::check(DemoDataSeeder::demoPassword(), $user->password);
    app(CurrentCompany::class)->set($company);
    $dash = app(DashboardService::class)->forUser($user);
    $reports = app(ReportService::class)->forUser($user, [
        'date_from' => now()->subMonths(6)->toDateString(),
        'date_to' => now()->toDateString(),
    ]);

    $out['users'][$key] = [
        'email' => $user->email,
        'roles' => $user->roles()->pluck('slug')->all(),
        'password_ok' => $passwordOk,
        'can_view_all_leads' => $user->canViewAllLeads(),
        'can_view_all_tasks' => $user->canViewAllTasks(),
        'is_admin_role' => $user->hasRole('admin'),
        'dashboard' => [
            'leadCount' => $dash['leadCount'] ?? null,
            'customerCount' => $dash['customerCount'] ?? ($dash['customersCount'] ?? null),
            'taskCount' => $dash['taskCount'] ?? null,
            'wonLeadsCount' => $dash['wonLeadsCount'] ?? null,
            'pendingTasksCount' => $dash['pendingTasksCount'] ?? null,
        ],
        'reports_has_leads' => isset($reports['leads']) || isset($reports['lead_stats']) || is_array($reports),
        'report_top_keys' => array_keys($reports),
    ];
}

echo json_encode($out, JSON_PRETTY_PRINT).PHP_EOL;
