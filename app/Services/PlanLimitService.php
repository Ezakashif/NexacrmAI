<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class PlanLimitService
{
    /**
     * @throws ValidationException
     */
    public function assertCanAddUser(Company $company, int $adding = 1): void
    {
        $this->assertWithinLimit($company, 'users', $adding, $this->userCount($company));
    }

    /**
     * @throws ValidationException
     */
    public function assertCanAddLead(Company $company, int $adding = 1): void
    {
        $this->assertWithinLimit($company, 'leads', $adding, $this->leadCount($company));
    }

    /**
     * @throws ValidationException
     */
    public function assertCanAddCustomer(Company $company, int $adding = 1): void
    {
        $this->assertWithinLimit($company, 'customers', $adding, $this->customerCount($company));
    }

    public function remaining(Company $company, string $metric): ?int
    {
        $company->loadMissing('plan.limits');
        $plan = $company->plan;
        $limit = $plan?->limitFor($metric);

        if ($limit?->isUnlimited()) {
            return null;
        }

        $max = $limit ? (int) $limit->limit_value : $plan?->{"max_{$metric}"};

        if ($max === null) {
            return null;
        }

        $current = match ($metric) {
            'users' => $this->userCount($company),
            'leads' => $this->leadCount($company),
            'customers' => $this->customerCount($company),
            default => 0,
        };

        return max(0, $max - $current);
    }

    /**
     * @throws ValidationException
     */
    private function assertWithinLimit(Company $company, string $metric, int $adding, int $current): void
    {
        $company->loadMissing('plan.limits');
        $plan = $company->plan;

        if (! $plan || $plan->isUnlimited($metric)) {
            return;
        }

        $limit = $plan->limitFor($metric);
        $max = $limit ? (int) $limit->limit_value : (int) $plan->{"max_{$metric}"};

        if (($current + $adding) > $max) {
            throw ValidationException::withMessages([
                $metric => sprintf(
                    'Your plan allows a maximum of %d %s. Upgrade your plan or contact support.',
                    $max,
                    $metric,
                ),
            ]);
        }
    }

    private function userCount(Company $company): int
    {
        return User::withoutCompanyScope()
            ->where('company_id', $company->id)
            ->where('is_super_admin', false)
            ->count();
    }

    private function leadCount(Company $company): int
    {
        return Lead::withoutCompanyScope()
            ->where('company_id', $company->id)
            ->count();
    }

    private function customerCount(Company $company): int
    {
        return Customer::withoutCompanyScope()
            ->where('company_id', $company->id)
            ->count();
    }
}
