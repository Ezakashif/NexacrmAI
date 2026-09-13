<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use App\Support\DashboardTour;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboard,
    ) {}

    public function index(): View
    {
        $user = auth()->user();

        return view('dashboard', array_merge(
            $this->dashboard->forUser($user),
            [
                'shouldStartDashboardTour' => ! $user->hasCompletedDashboardTour(),
                'dashboardTourSteps' => DashboardTour::stepsFor($user),
            ],
        ));
    }
}
