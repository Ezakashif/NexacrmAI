<?php

namespace Database\Factories;

use App\Models\ContactInquiry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactInquiry>
 */
class ContactInquiryFactory extends Factory
{
    protected $model = ContactInquiry::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'company' => fake()->optional()->company(),
            'phone' => fake()->optional()->numerify('+1 555 ### ####'),
            'message' => fake()->paragraph(),
            'intent' => fake()->randomElement(['demo', 'general', null]),
            'status' => ContactInquiry::STATUS_NEW,
            'ip_address' => fake()->ipv4(),
            'user_agent' => 'PHPUnit',
        ];
    }

    public function demo(): static
    {
        return $this->state(fn () => [
            'intent' => 'demo',
            'message' => 'We would like to book a demo of Algos.',
        ]);
    }

    public function reviewed(): static
    {
        return $this->state(fn () => [
            'status' => ContactInquiry::STATUS_REVIEWED,
            'reviewed_at' => now(),
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn () => [
            'status' => ContactInquiry::STATUS_CLOSED,
            'reviewed_at' => now(),
        ]);
    }
}
