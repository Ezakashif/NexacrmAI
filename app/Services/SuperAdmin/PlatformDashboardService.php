<?php

namespace App\Services\SuperAdmin;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PlatformDashboardService
{
    /**
     * @return array<string, int>
     */
    public function stats(): array
    {
        return [
            'companies_total' => Company::query()->count(),
            'companies_active' => Company::query()->active()->count(),
            'companies_suspended' => Company::query()->suspended()->count(),
            'companies_trial' => Company::query()->onTrial()->count(),
            'companies_expired' => Company::query()->subscriptionExpired()->count(),
            'tenant_users' => User::withoutCompanyScope()->where('is_super_admin', false)->count(),
            'super_admin_users' => User::withoutCompanyScope()->where('is_super_admin', true)->count(),
            'companies_new_this_month' => Company::query()
                ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->count(),
            'leads_total' => Lead::withoutCompanyScope()->count(),
            'customers_total' => Customer::withoutCompanyScope()->count(),
        ];
    }

    /**
     * @return array{labels: list<string>, values: list<int>}
     */
    public function companiesGrowth(int $months = 12): array
    {
        return $this->monthlyCounts(
            Company::query()->toBase(),
            'companies',
            $months,
        );
    }

    /**
     * @return array{labels: list<string>, values: list<int>}
     */
    public function leadsGrowth(int $months = 12): array
    {
        return $this->monthlyCounts(
            Lead::withoutCompanyScope()->toBase(),
            'leads',
            $months,
        );
    }

    /**
     * @return array{labels: list<string>, values: list<int>}
     */
    public function customersGrowth(int $months = 12): array
    {
        return $this->monthlyCounts(
            Customer::withoutCompanyScope()->toBase(),
            'customers',
            $months,
        );
    }

    /**
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return array{labels: list<string>, values: list<int>}
     */
    private function monthlyCounts($query, string $table, int $months): array
    {
        $months = max(1, min(24, $months));
        $start = now()->startOfMonth()->subMonths($months - 1);

        $driver = DB::connection()->getDriverName();
        $periodExpression = match ($driver) {
            'sqlite' => "strftime('%Y-%m', created_at)",
            'pgsql' => "to_char(created_at, 'YYYY-MM')",
            default => "DATE_FORMAT(created_at, '%Y-%m')",
        };

        $rows = $query
            ->where("{$table}.created_at", '>=', $start)
            ->selectRaw("{$periodExpression} as period, COUNT(*) as aggregate")
            ->groupBy('period')
            ->orderBy('period')
            ->pluck('aggregate', 'period');

        return $this->fillMonthSeries($start, $months, $rows);
    }

    /**
     * @param  Collection<string, int|string>  $rows
     * @return array{labels: list<string>, values: list<int>}
     */
    private function fillMonthSeries(Carbon $start, int $months, Collection $rows): array
    {
        $labels = [];
        $values = [];
        $cursor = $start->copy();

        for ($i = 0; $i < $months; $i++) {
            $key = $cursor->format('Y-m');
            $labels[] = $cursor->format('M Y');
            $values[] = (int) ($rows[$key] ?? 0);
            $cursor->addMonth();
        }

        return compact('labels', 'values');
    }
}
