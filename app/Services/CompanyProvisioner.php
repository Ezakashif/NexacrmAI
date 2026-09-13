<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Plan;
use App\Models\User;
use App\Services\SuperAdmin\PlatformSettingsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CompanyProvisioner
{
    public function __construct(
        private RbacRoleSynchronizer $roleSynchronizer,
        private PlatformSettingsService $settings,
    ) {}

    /**
     * Create a company, sync default roles, and optionally provision the first admin.
     *
     * @param  array{
     *     name: string,
     *     slug?: string|null,
     *     status?: string,
     *     email?: string|null,
     *     phone?: string|null,
     *     plan_id?: int|null,
     *     subscription_status?: string|null,
     *     trial_ends_at?: string|null,
     *     admin_name?: string|null,
     *     admin_email?: string|null,
     *     admin_password?: string|null,
     *     mark_admin_email_verified?: bool
     * }  $data
     * @return array{company: Company, admin: User|null}
     */
    public function provision(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $slug = $data['slug'] ?? null;
            $slug = filled($slug) ? Str::slug((string) $slug) : Str::slug($data['name']);

            $configuredPlanId = $this->settings->getInt('default_plan_id', 0);
            $planId = $data['plan_id']
                ?? Plan::query()->active()->whereKey($configuredPlanId)->value('id')
                ?? Plan::default()?->id;
            $subscriptionStatus = $data['subscription_status']
                ?? Company::SUBSCRIPTION_TRIAL;
            $trialEndsAt = $data['trial_ends_at'] ?? null;

            if ($subscriptionStatus === Company::SUBSCRIPTION_TRIAL && blank($trialEndsAt)) {
                $trialEndsAt = now()->addDays($this->settings->getInt('trial_duration_days', 14));
            }

            $company = Company::query()->create([
                'name' => $data['name'],
                'slug' => $this->uniqueSlug($slug),
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'status' => $data['status'] ?? $this->settings->get('default_company_status', Company::STATUS_ACTIVE),
                'plan_id' => $planId,
                'subscription_status' => $subscriptionStatus,
                'trial_ends_at' => $trialEndsAt,
            ]);

            $this->roleSynchronizer->syncDefaultRolesForCompany($company);

            $admin = null;

            if (filled($data['admin_email'] ?? null)) {
                // Admin/Super Admin provisioning marks verified by default.
                // Public self-registration opts out so MustVerifyEmail is real.
                $markVerified = (bool) ($data['mark_admin_email_verified'] ?? true);

                $admin = new User;
                $admin->forceFill([
                    'name' => $data['admin_name'] ?: 'Administrator',
                    'email' => $data['admin_email'],
                    'password' => $data['admin_password'] ?: Str::password(16),
                    'role' => 'admin',
                    'status' => 'active',
                    'email_verified_at' => $markVerified ? now() : null,
                    'is_super_admin' => false,
                ]);
                $admin->company_id = $company->id;
                $admin->save();
                $admin->syncRolesFromLegacyColumn();

                $company->update([
                    'owner_id' => $admin->id,
                    'email' => $company->email ?: $admin->email,
                ]);
            }

            ActivityLogger::log('company.created', $company, [
                'name' => $company->name,
                'slug' => $company->slug,
            ]);

            return compact('company', 'admin');
        });
    }

    private function uniqueSlug(string $base): string
    {
        $slug = $base !== '' ? $base : 'company';
        $candidate = $slug;
        $i = 1;

        while (Company::withTrashed()->where('slug', $candidate)->exists()) {
            $candidate = $slug.'-'.$i;
            $i++;
        }

        return $candidate;
    }
}
