<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Services\SuperAdmin\PlatformSettingsService;
use App\Http\Controllers\Marketing\PricingController;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(PlatformSettingsService $settings): View|RedirectResponse
    {
        if (auth()->check()) {
            if (auth()->user()->isSuperAdmin()) {
                return redirect()->route('superadmin.dashboard');
            }

            return redirect()->route('dashboard');
        }

        $plans = PricingController::publicPlans();

        return view('marketing.home', [
            'trialDays' => max(1, $settings->getInt('trial_duration_days', 14)),
            'plans' => $plans,
        ]);
    }
}
