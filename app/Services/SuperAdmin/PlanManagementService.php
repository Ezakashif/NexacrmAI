<?php

namespace App\Services\SuperAdmin;

use App\Models\Plan;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class PlanManagementService
{
    public function create(array $data, int $actorId): Plan
    {
        return DB::transaction(function () use ($data, $actorId): Plan {
            $plan = Plan::query()->create(array_merge(
                $this->planAttributes($data),
                ['created_by' => $actorId, 'updated_by' => $actorId],
            ));

            $this->syncDetails($plan, $data);

            return $plan->load(['features', 'limits']);
        });
    }

    public function update(Plan $plan, array $data, int $actorId): Plan
    {
        return DB::transaction(function () use ($plan, $data, $actorId): Plan {
            $plan->update(array_merge($this->planAttributes($data), ['updated_by' => $actorId]));
            $this->syncDetails($plan, $data);

            return $plan->fresh(['features', 'limits']);
        });
    }

    public function duplicate(Plan $plan, int $actorId): Plan
    {
        return DB::transaction(function () use ($plan, $actorId): Plan {
            $copy = $plan->replicate([
                'slug', 'is_default', 'created_at', 'updated_at', 'deleted_at',
            ]);
            $copy->name = $plan->name.' copy';
            $copy->slug = $this->nextCopySlug($plan->slug);
            $copy->is_default = false;
            $copy->is_active = false;
            $copy->is_public = false;
            $copy->created_by = $actorId;
            $copy->updated_by = $actorId;
            $copy->save();

            foreach ($plan->features as $feature) {
                $copy->features()->create(Arr::except($feature->getAttributes(), ['id', 'plan_id', 'created_at', 'updated_at']));
            }

            foreach ($plan->limits as $limit) {
                $copy->limits()->create(Arr::except($limit->getAttributes(), ['id', 'plan_id', 'created_at', 'updated_at']));
            }

            return $copy->load(['features', 'limits']);
        });
    }

    private function planAttributes(array $data): array
    {
        return array_merge(Arr::only($data, [
            'name', 'slug', 'short_description', 'description', 'monthly_price',
            'yearly_price', 'currency', 'billing_cycle', 'trial_days', 'sort_order', 'notes',
        ]), [
            'currency' => strtoupper($data['currency']),
            'is_free' => (bool) ($data['is_free'] ?? false),
            'is_featured' => (bool) ($data['is_featured'] ?? false),
            'is_public' => (bool) ($data['is_public'] ?? false),
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);
    }

    private function syncDetails(Plan $plan, array $data): void
    {
        $plan->features()->delete();
        $plan->limits()->delete();

        foreach ($data['features'] ?? [] as $index => $feature) {
            $plan->features()->create(array_merge($feature, [
                'sort_order' => $feature['sort_order'] ?? $index + 1,
                'is_highlighted' => (bool) ($feature['is_highlighted'] ?? false),
            ]));
        }

        foreach ($data['limits'] ?? [] as $index => $limit) {
            $plan->limits()->create(array_merge($limit, [
                'sort_order' => $limit['sort_order'] ?? $index + 1,
            ]));
        }
    }

    private function nextCopySlug(string $slug): string
    {
        $base = $slug.'-copy';
        $candidate = $base;
        $suffix = 2;

        while (Plan::withTrashed()->where('slug', $candidate)->exists()) {
            $candidate = $base.'-'.$suffix++;
        }

        return $candidate;
    }
}
