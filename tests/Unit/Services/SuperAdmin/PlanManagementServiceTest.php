<?php

namespace Tests\Unit\Services\SuperAdmin;

use App\Models\Plan;
use App\Models\PlanFeature;
use App\Models\PlanLimit;
use App\Models\User;
use App\Services\SuperAdmin\PlanManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanManagementServiceTest extends TestCase
{
    use RefreshDatabase;

    private PlanManagementService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(PlanManagementService::class);
    }

    public function test_create_persists_plan_with_features_and_limits(): void
    {
        $actor = User::factory()->superAdmin()->create();

        $plan = $this->service->create([
            'name' => 'Growth',
            'slug' => 'growth-unit-test',
            'short_description' => 'For growing teams',
            'description' => 'Full growth plan',
            'monthly_price' => 49,
            'yearly_price' => 490,
            'currency' => 'usd',
            'billing_cycle' => 'both',
            'trial_days' => 14,
            'sort_order' => 2,
            'notes' => 'Popular tier',
            'is_free' => false,
            'is_featured' => true,
            'is_public' => true,
            'is_active' => true,
            'features' => [
                [
                    'feature_key' => 'reports',
                    'feature_name' => 'Advanced reports',
                    'feature_type' => 'boolean',
                    'is_highlighted' => true,
                ],
            ],
            'limits' => [
                [
                    'limit_key' => 'users',
                    'limit_name' => 'Users',
                    'limit_value' => '25',
                    'unit' => 'count',
                ],
            ],
        ], $actor->id);

        $this->assertDatabaseHas('plans', [
            'id' => $plan->id,
            'slug' => 'growth-unit-test',
            'currency' => 'USD',
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);
        $this->assertCount(1, $plan->features);
        $this->assertSame('reports', $plan->features->first()->feature_key);
        $this->assertCount(1, $plan->limits);
        $this->assertSame('25', $plan->limits->first()->limit_value);
    }

    public function test_update_replaces_features_and_limits(): void
    {
        $actor = User::factory()->superAdmin()->create();
        $plan = Plan::factory()->create(['created_by' => $actor->id, 'updated_by' => $actor->id]);

        PlanFeature::factory()->create([
            'plan_id' => $plan->id,
            'feature_key' => 'old-feature',
        ]);
        PlanLimit::factory()->create([
            'plan_id' => $plan->id,
            'limit_key' => 'old-limit',
        ]);

        $updated = $this->service->update($plan, [
            'name' => 'Updated Plan',
            'slug' => $plan->slug,
            'short_description' => $plan->short_description,
            'description' => $plan->description,
            'monthly_price' => 59,
            'yearly_price' => 590,
            'currency' => 'USD',
            'billing_cycle' => 'both',
            'trial_days' => 7,
            'sort_order' => 3,
            'notes' => null,
            'is_free' => false,
            'is_featured' => false,
            'is_public' => true,
            'is_active' => true,
            'features' => [
                [
                    'feature_key' => 'new-feature',
                    'feature_name' => 'New feature',
                    'feature_type' => 'boolean',
                ],
            ],
            'limits' => [
                [
                    'limit_key' => 'leads',
                    'limit_name' => 'Leads',
                    'limit_value' => '500',
                    'unit' => 'count',
                ],
            ],
        ], $actor->id);

        $this->assertSame('Updated Plan', $updated->name);
        $this->assertSame(['new-feature'], $updated->features->pluck('feature_key')->all());
        $this->assertSame(['leads'], $updated->limits->pluck('limit_key')->all());
        $this->assertDatabaseMissing('plan_features', ['plan_id' => $plan->id, 'feature_key' => 'old-feature']);
    }

    public function test_duplicate_creates_inactive_copy_with_unique_slug_and_cloned_details(): void
    {
        $actor = User::factory()->superAdmin()->create();
        $plan = Plan::factory()->create([
            'name' => 'Starter',
            'slug' => 'starter-unit-test',
            'is_default' => true,
            'is_active' => true,
            'is_public' => true,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);

        PlanFeature::factory()->create([
            'plan_id' => $plan->id,
            'feature_key' => 'csv-import',
        ]);
        PlanLimit::factory()->create([
            'plan_id' => $plan->id,
            'limit_key' => 'users',
            'limit_value' => '10',
        ]);

        $copy = $this->service->duplicate($plan, $actor->id);

        $this->assertNotSame($plan->id, $copy->id);
        $this->assertSame('Starter copy', $copy->name);
        $this->assertSame('starter-unit-test-copy', $copy->slug);
        $this->assertFalse($copy->is_default);
        $this->assertFalse($copy->is_active);
        $this->assertFalse($copy->is_public);
        $this->assertSame(['csv-import'], $copy->features->pluck('feature_key')->all());
        $this->assertSame(['users'], $copy->limits->pluck('limit_key')->all());
    }

    public function test_duplicate_increments_slug_when_copy_already_exists(): void
    {
        $actor = User::factory()->superAdmin()->create();
        $plan = Plan::factory()->create([
            'slug' => 'pro',
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);

        Plan::factory()->create(['slug' => 'pro-copy']);

        $copy = $this->service->duplicate($plan, $actor->id);

        $this->assertSame('pro-copy-2', $copy->slug);
    }
}
