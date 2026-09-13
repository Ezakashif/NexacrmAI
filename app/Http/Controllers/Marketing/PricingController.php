<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class PricingController extends Controller
{
    public function index(): View
    {
        $plans = $this->publicPlans();

        return view('marketing.pricing', [
            'plans' => $plans,
            'comparisonRows' => $this->comparisonRows($plans),
        ]);
    }

    /** @return \Illuminate\Support\Collection<int, Plan> */
    public static function publicPlans(): \Illuminate\Support\Collection
    {
        if (! Schema::hasTable('plans')) {
            return collect();
        }

        return Plan::query()
            ->active()
            ->where('is_public', true)
            ->with(['features', 'limits'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    private function comparisonRows(\Illuminate\Support\Collection $plans): array
    {
        $labels = [];
        foreach ($plans as $plan) {
            foreach ($plan->features as $feature) $labels['feature:'.$feature->feature_key] = $feature->feature_name;
            foreach ($plan->limits as $limit) $labels['limit:'.$limit->limit_key] = $limit->limit_name;
        }
        return collect($labels)->map(function (string $label, string $key) use ($plans): array {
            $row = ['feature' => $label];
            foreach ($plans as $plan) {
                [$type, $name] = explode(':', $key, 2);
                $item = $type === 'feature' ? $plan->features->firstWhere('feature_key', $name) : $plan->limits->firstWhere('limit_key', $name);
                $row[$plan->id] = $type === 'limit'
                    ? ($item ? ($item->isUnlimited() ? 'Unlimited' : trim($item->limit_value.' '.$item->unit)) : false)
                    : ($item ? ($item->feature_type === 'boolean' ? true : ($item->feature_value ?: true)) : false);
            }
            return $row;
        })->values()->all();
    }
}
