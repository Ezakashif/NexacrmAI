<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => ucwords($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
            'max_users' => 10,
            'max_leads' => 1000,
            'max_customers' => 500,
            'price_cents' => 2900,
            'short_description' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'monthly_price' => 29,
            'yearly_price' => 290,
            'currency' => 'USD',
            'billing_cycle' => 'both',
            'trial_days' => 14,
            'is_free' => false,
            'is_featured' => false,
            'is_public' => true,
            'sort_order' => 1,
            'is_default' => false,
            'is_active' => true,
        ];
    }

    public function default(): static
    {
        return $this->state(fn () => [
            'is_default' => true,
            'price_cents' => 0,
        ]);
    }

    public function public(): static
    {
        return $this->state(fn () => ['is_active' => true, 'is_public' => true]);
    }
}
