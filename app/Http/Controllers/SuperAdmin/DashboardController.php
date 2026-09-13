<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\ContactInquiry;
use App\Services\SuperAdmin\PlatformAlertService;
use App\Services\SuperAdmin\PlatformDashboardService;
use App\Services\SuperAdmin\SystemHealthService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected PlatformDashboardService $dashboard,
        protected PlatformAlertService $alerts,
        protected SystemHealthService $health,
    ) {}

    public function index(): View
    {
        return view('superadmin.dashboard', [
            'stats' => $this->dashboard->stats(),
            'alerts' => $this->alerts->alerts(),
            'health' => $this->health->snapshot(),
            'recentCompanies' => Company::query()
                ->with(['owner:id,name,email', 'plan:id,name'])
                ->withCount(['users'])
                ->latest()
                ->limit(5)
                ->get(),
            'recentContactInquiries' => ContactInquiry::query()
                ->latest()
                ->limit(5)
                ->get(),
            'newContactInquiryCount' => ContactInquiry::query()->new()->count(),
            'recentActivity' => ActivityLog::withoutCompanyScope()
                ->forPlatform()
                ->with([
                    'actor:id,name,email',
                    'company:id,name,slug',
                ])
                ->latest()
                ->limit(15)
                ->get(),
            'charts' => [
                'companies' => $this->dashboard->companiesGrowth(12),
                'leads' => $this->dashboard->leadsGrowth(12),
                'customers' => $this->dashboard->customersGrowth(12),
            ],
        ]);
    }
}
