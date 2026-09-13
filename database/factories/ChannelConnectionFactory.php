<?php

namespace Database\Factories;

use App\Enums\Channels\ChannelConnectionStatus;
use App\Enums\Channels\ChannelProvider;
use App\Models\ChannelConnection;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ChannelConnection>
 */
class ChannelConnectionFactory extends Factory
{
    protected $model = ChannelConnection::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'uuid' => (string) Str::uuid(),
            'provider' => ChannelProvider::GenericWebhook,
            'name' => 'Generic Webhook',
            'status' => ChannelConnectionStatus::Connected,
            'external_account_id' => 'acct_'.Str::lower(Str::random(8)),
            'webhook_secret' => 'test-webhook-secret',
            'verify_token' => Str::random(32),
            'meta' => [],
        ];
    }
}
