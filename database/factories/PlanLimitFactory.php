<?php

namespace Database\Factories;

use App\Models\Plan;
use App\Models\PlanLimit;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PlanLimit> */
class PlanLimitFactory extends Factory
{
    protected $model = PlanLimit::class;

    public function definition(): array
    {
        return [
            'plan_id' => Plan::factory(),
            'limit_key' => fake()->unique()->slug(2),
            'limit_name' => fake()->words(2, true),
            'limit_value' => (string) fake()->numberBetween(1, 1000),
            'unit' => 'count',
            'sort_order' => 1,
        ];
    }
}
