<?php

namespace Database\Factories;

use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeadActivity>
 */
class LeadActivityFactory extends Factory
{
    protected $model = LeadActivity::class;

    public function definition(): array
    {
        return [
            'lead_id' => Lead::factory(),
            'user_id' => User::factory(),
            'type' => fake()->randomElement(['call', 'whatsapp', 'email', 'meeting', 'note']),
            'summary' => fake()->optional(0.9)->sentence(),
            'occurred_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'next_follow_up_date' => fake()->optional(0.5)->dateTimeBetween('now', '+14 days'),
        ];
    }

    public function type(string $type): static
    {
        return $this->state(fn () => ['type' => $type]);
    }
}
