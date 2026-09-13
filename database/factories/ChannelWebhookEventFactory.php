<?php

namespace Database\Factories;

use App\Enums\Channels\ChannelProvider;
use App\Enums\Channels\WebhookEventStatus;
use App\Models\ChannelConnection;
use App\Models\ChannelWebhookEvent;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ChannelWebhookEvent>
 */
class ChannelWebhookEventFactory extends Factory
{
    protected $model = ChannelWebhookEvent::class;

    public function definition(): array
    {
        $payload = json_encode([
            'type' => 'lead',
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->numerify('+1555#######'),
        ], JSON_THROW_ON_ERROR);

        return [
            'company_id' => Company::factory(),
            'channel_connection_id' => null,
            'uuid' => (string) Str::uuid(),
            'provider' => ChannelProvider::GenericWebhook,
            'event_type' => 'lead',
            'idempotency_key' => hash('sha256', Str::uuid()->toString()),
            'status' => WebhookEventStatus::Queued,
            'attempts' => 0,
            'headers' => [],
            'payload' => $payload,
            'signature' => null,
            'signature_valid' => true,
        ];
    }

    public function forConnection(ChannelConnection $connection): static
    {
        return $this->state(fn () => [
            'company_id' => $connection->company_id,
            'channel_connection_id' => $connection->id,
            'provider' => $connection->provider,
        ]);
    }
}
