<?php

namespace Database\Factories;

use App\Models\Lead;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    protected $model = Lead::class;

    public function definition(): array
    {
        return [
            'created_by' => User::factory(),
            'assigned_to' => null,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'company' => fake()->optional(0.8)->company(),
            'source' => fake()->randomElement([
                'website',
                'facebook',
                'referral',
                'whatsapp',
                'linkedin',
                'cold_call',
            ]),
            'status' => 'new',
            'sort_order' => 0,
            'estimated_value' => fake()->optional(0.7)->randomFloat(2, 500, 50000),
            'notes' => fake()->optional(0.5)->paragraph(),
            'follow_up_date' => fake()->optional(0.6)->dateTimeBetween('now', '+30 days'),
        ];
    }

    public function status(string $status, int $sortOrder = 0): static
    {
        return $this->state(fn () => [
            'status' => $status,
            'sort_order' => $sortOrder,
        ]);
    }

    public function assignedTo(User $user): static
    {
        return $this->state(fn () => [
            'assigned_to' => $user->id,
            'company_id' => $user->company_id,
            'created_by' => $user->id,
        ]);
    }

    public function createdAt(Carbon|\DateTimeInterface|string $timestamp): static
    {
        $value = $timestamp instanceof Carbon
            ? $timestamp
            : Carbon::parse($timestamp);

        return $this->state(fn () => [
            'created_at' => $value,
            'updated_at' => $value,
        ]);
    }
}
