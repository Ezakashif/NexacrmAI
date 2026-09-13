<?php

namespace App\Services\SuperAdmin;

use App\Models\Company;
use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class PlatformAlertService
{
    /**
     * @return list<array{type: string, severity: string, title: string, message: string, meta?: array<string, mixed>}>
     */
    public function alerts(): array
    {
        $alerts = [];

        $companiesWithoutUsers = Company::query()
            ->whereDoesntHave('users')
            ->count();

        if ($companiesWithoutUsers > 0) {
            $alerts[] = [
                'type' => 'companies_without_users',
                'severity' => 'warning',
                'title' => 'Companies with no users',
                'message' => "{$companiesWithoutUsers} ".str('company')->plural($companiesWithoutUsers).' have no users assigned.',
                'meta' => ['count' => $companiesWithoutUsers],
            ];
        }

        $inactiveCompanies = Company::query()
            ->active()
            ->inactiveForDays(30)
            ->count();

        if ($inactiveCompanies > 0) {
            $alerts[] = [
                'type' => 'companies_inactive',
                'severity' => 'warning',
                'title' => 'Inactive companies',
                'message' => "{$inactiveCompanies} active ".str('company')->plural($inactiveCompanies).' have no activity in 30 days.',
                'meta' => ['count' => $inactiveCompanies, 'days' => 30],
            ];
        }

        $overLimit = $this->companiesExceedingLimits();

        if ($overLimit > 0) {
            $alerts[] = [
                'type' => 'companies_over_limit',
                'severity' => 'danger',
                'title' => 'Companies exceeding plan limits',
                'message' => "{$overLimit} ".str('company')->plural($overLimit).' exceed plan user, lead, or customer limits.',
                'meta' => ['count' => $overLimit],
            ];
        }

        $failedJobs = $this->failedJobsCount();

        if ($failedJobs > 0) {
            $alerts[] = [
                'type' => 'failed_jobs',
                'severity' => 'danger',
                'title' => 'Failed queue jobs',
                'message' => "{$failedJobs} failed ".str('job')->plural($failedJobs).' awaiting retry or cleanup.',
                'meta' => ['count' => $failedJobs],
            ];
        }

        if (PlatformSetting::query()->where('key', 'maintenance_mode')->where('value', '1')->exists()) {
            $alerts[] = [
                'type' => 'maintenance_mode',
                'severity' => 'info',
                'title' => 'Maintenance mode enabled',
                'message' => 'Platform maintenance mode is currently turned on in system settings.',
            ];
        }

        $expired = Company::query()->subscriptionExpired()->active()->count();

        if ($expired > 0) {
            $alerts[] = [
                'type' => 'subscriptions_expired',
                'severity' => 'warning',
                'title' => 'Expired subscriptions',
                'message' => "{$expired} active ".str('company')->plural($expired).' have an expired trial or subscription.',
                'meta' => ['count' => $expired],
            ];
        }

        return $alerts;
    }

    private function companiesExceedingLimits(): int
    {
        return Company::query()
            ->with('plan')
            ->withCount(['users', 'leads', 'customers'])
            ->get()
            ->filter(function (Company $company) {
                $plan = $company->plan;

                if (! $plan) {
                    return false;
                }

                return ($plan->max_users !== null && $company->users_count > $plan->max_users)
                    || ($plan->max_leads !== null && $company->leads_count > $plan->max_leads)
                    || ($plan->max_customers !== null && $company->customers_count > $plan->max_customers);
            })
            ->count();
    }

    private function failedJobsCount(): int
    {
        if (! Schema::hasTable('failed_jobs')) {
            return 0;
        }

        return (int) DB::table('failed_jobs')->count();
    }
}
