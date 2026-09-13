<?php

namespace Database\Factories;

use App\Enums\Channels\ChannelProvider;
use App\Models\ChannelContact;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ChannelContact>
 */
class ChannelContactFactory extends Factory
{
    protected $model = ChannelContact::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'provider' => ChannelProvider::GenericWebhook,
            'external_user_id' => 'ext_'.Str::lower(Str::random(10)),
            'email' => fake()->safeEmail(),
            'phone' => fake()->numerify('+1555#######'),
            'display_name' => fake()->name(),
            'meta' => [],
        ];
    }
}
