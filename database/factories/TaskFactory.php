<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'created_by' => User::factory(),
            'assigned_to' => null,
            'customer_id' => null,
            'lead_id' => null,
            'title' => fake()->sentence(4),
            'description' => fake()->optional(0.7)->paragraph(),
            'priority' => fake()->randomElement(['low', 'medium', 'high', 'urgent']),
            'status' => 'pending',
            'sort_order' => 0,
            'due_date' => fake()->optional(0.8)->dateTimeBetween('now', '+14 days'),
            'completed_at' => null,
        ];
    }

    public function status(string $status, int $sortOrder = 0): static
    {
        return $this->state(fn () => [
            'status' => $status,
            'sort_order' => $sortOrder,
            'completed_at' => $status === 'completed' ? now() : null,
        ]);
    }

    public function forCustomer(Customer $customer): static
    {
        return $this->state(fn () => ['customer_id' => $customer->id]);
    }

    public function forLead(Lead $lead): static
    {
        return $this->state(fn () => ['lead_id' => $lead->id]);
    }

    public function assignedTo(User $user): static
    {
        return $this->state(fn () => [
            'assigned_to' => $user->id,
            'company_id' => $user->company_id,
            'created_by' => $user->id,
        ]);
    }
}
