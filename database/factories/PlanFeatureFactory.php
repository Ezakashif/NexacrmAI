<?php

namespace Database\Factories;

use App\Models\Plan;
use App\Models\PlanFeature;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PlanFeature> */
class PlanFeatureFactory extends Factory
{
    protected $model = PlanFeature::class;

    public function definition(): array
    {
        return [
            'plan_id' => Plan::factory(),
            'feature_key' => fake()->unique()->slug(2),
            'feature_name' => fake()->words(2, true),
            'feature_type' => 'boolean',
            'feature_value' => null,
            'sort_order' => 1,
            'is_highlighted' => false,
        ];
    }
}
