<?php

namespace App\Services\SuperAdmin;

use App\Models\ActivityLog;
use App\Models\Company;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class CompanyProfileService
{
    /**
     * @return array<string, mixed>
     */
    public function profile(Company $company): array
    {
        $company->load([
            'owner:id,name,email,last_login_at',
            'plan:id,name,slug,max_users,max_leads,max_customers',
        ])->loadCount([
            'users',
            'leads',
            'customers',
            'tasks',
            'activityLogs',
            'leadActivities',
        ]);

        $users = $company->users()
            ->with('roles:id,name,slug,company_id')
            ->orderBy('name')
            ->limit(50)
            ->get();

        $recentActivity = ActivityLog::withoutCompanyScope()
            ->forTenant()
            ->with(['actor:id,name,email'])
            ->where('company_id', $company->id)
            ->latest()
            ->limit(20)
            ->get();

        $admins = $users->filter(fn ($user) => $user->roles->contains('slug', 'admin'))->values();

        return [
            'company' => $company,
            'users' => $users,
            'admins' => $admins,
            'recentActivity' => $recentActivity,
            'usage' => [
                'users' => $company->users_count,
                'leads' => $company->leads_count,
                'customers' => $company->customers_count,
                'tasks' => $company->tasks_count,
                'activities' => $company->activity_logs_count + $company->lead_activities_count,
                'storage_bytes' => $this->estimateStorageBytes($company, $users),
            ],
            'lastLogin' => $company->users()
                ->whereNotNull('last_login_at')
                ->max('last_login_at'),
        ];
    }

    /**
     * @param  Collection<int, \App\Models\User>  $users
     */
    private function estimateStorageBytes(Company $company, Collection $users): int
    {
        $bytes = 0;

        if (filled($company->logo_path) && Storage::disk('public')->exists($company->logo_path)) {
            $bytes += (int) Storage::disk('public')->size($company->logo_path);
        }

        foreach ($users as $user) {
            if (filled($user->photo_path) && Storage::disk('public')->exists($user->photo_path)) {
                $bytes += (int) Storage::disk('public')->size($user->photo_path);
            }
        }

        return $bytes;
    }
}
