<?php

namespace App\Services\Analytics;

use App\Models\Lead;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;

class CrmAnalytics
{
    public static function applyOverdueTasks(
        Builder $query,
        string $statusColumn = 'status',
        string $dueColumn = 'due_date',
    ): Builder {
        return $query
            ->whereNotIn($statusColumn, ['completed', 'cancelled'])
            ->whereNotNull($dueColumn)
            ->whereDate($dueColumn, '<', today());
    }

    /**
     * @return array{labels: list<string>, data: list<int>}
     */
    public static function monthlyLeadGrowth(
        Builder $leadQuery,
        int $months,
        string $createdAtColumn = 'created_at',
    ): array {
        $start = now()->startOfMonth()->subMonths($months - 1);
        $end = now()->endOfMonth();

        $monthExpression = self::monthExpression($createdAtColumn, $leadQuery);

        $counts = (clone $leadQuery)
            ->where($createdAtColumn, '>=', $start)
            ->selectRaw("{$monthExpression} as month_key, COUNT(*) as aggregate")
            ->groupBy('month_key')
            ->pluck('aggregate', 'month_key');

        $labels = [];
        $data = [];

        foreach (CarbonPeriod::create($start, '1 month', $end) as $month) {
            /** @var Carbon $month */
            $key = $month->format('Y-m');
            $labels[] = $month->format('M Y');
            $data[] = (int) ($counts[$key] ?? 0);
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    /**
     * @return array{labels: list<string>, data: list<int>}
     */
    public static function leadSourceDistribution(
        Builder $leadQuery,
        string $sourceColumn = 'source',
    ): array {
        $rows = (clone $leadQuery)
            ->selectRaw("{$sourceColumn} as source, COUNT(*) as aggregate")
            ->groupBy($sourceColumn)
            ->get();

        $counts = [];

        foreach ($rows as $row) {
            $key = filled($row->source) ? (string) $row->source : '__other__';
            $counts[$key] = ($counts[$key] ?? 0) + (int) $row->aggregate;
        }

        $labels = [];
        $data = [];

        foreach (Lead::SOURCES as $source) {
            $labels[] = ucfirst(str_replace('_', ' ', $source));
            $data[] = (int) ($counts[$source] ?? 0);
            unset($counts[$source]);
        }

        $other = (int) array_sum($counts);

        if ($other > 0) {
            $labels[] = 'Other / Unspecified';
            $data[] = $other;
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    public static function monthExpression(string $column, ?Builder $query = null): string
    {
        $driver = ($query ?? Lead::query())->getConnection()->getDriverName();

        return $driver === 'sqlite'
            ? "strftime('%Y-%m', {$column})"
            : "DATE_FORMAT({$column}, '%Y-%m')";
    }

    public static function dateExpression(string $column, ?Builder $query = null): string
    {
        $driver = ($query ?? Lead::query())->getConnection()->getDriverName();

        return $driver === 'sqlite'
            ? "strftime('%Y-%m-%d', {$column})"
            : "DATE({$column})";
    }
}
