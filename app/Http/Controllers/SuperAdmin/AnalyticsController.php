<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\SuperAdmin\PlatformDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function __construct(
        protected PlatformDashboardService $dashboard,
    ) {}

    public function companies(Request $request): JsonResponse
    {
        $months = (int) $request->integer('months', 12);

        return response()->json($this->dashboard->companiesGrowth($months));
    }

    public function leads(Request $request): JsonResponse
    {
        $months = (int) $request->integer('months', 12);

        return response()->json($this->dashboard->leadsGrowth($months));
    }

    public function customers(Request $request): JsonResponse
    {
        $months = (int) $request->integer('months', 12);

        return response()->json($this->dashboard->customersGrowth($months));
    }
}
