<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Plan;
use App\Services\RbacRoleSynchronizer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function configure(): static
    {
        return $this->afterCreating(function (Company $company) {
            app(RbacRoleSynchronizer::class)->syncDefaultRolesForCompany($company);
        });
    }

    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
            'email' => fake()->companyEmail(),
            'phone' => fake()->optional()->phoneNumber(),
            'status' => Company::STATUS_ACTIVE,
            'subscription_status' => Company::SUBSCRIPTION_ACTIVE,
            'plan_id' => Plan::query()->where('is_default', true)->value('id')
                ?? Plan::factory()->default(),
            'trial_ends_at' => null,
            'last_active_at' => now(),
        ];
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Company::STATUS_SUSPENDED,
        ]);
    }

    public function onTrial(?int $days = 14): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_status' => Company::SUBSCRIPTION_TRIAL,
            'trial_ends_at' => now()->addDays($days),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_status' => Company::SUBSCRIPTION_EXPIRED,
            'trial_ends_at' => now()->subDay(),
        ]);
    }
}
