<?php

namespace Database\Factories;

use App\Enums\Channels\ChannelProvider;
use App\Models\ChannelContact;
use App\Models\Company;
use App\Models\Conversation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Conversation>
 */
class ConversationFactory extends Factory
{
    protected $model = Conversation::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'channel_contact_id' => ChannelContact::factory(),
            'provider' => ChannelProvider::GenericWebhook,
            'external_thread_id' => 'thread_'.Str::lower(Str::random(8)),
            'status' => Conversation::STATUS_OPEN,
            'last_message_at' => now(),
            'unread_count' => 0,
            'meta' => [],
        ];
    }
}
