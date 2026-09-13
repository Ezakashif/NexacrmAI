<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ConversationMessage>
 */
class ConversationMessageFactory extends Factory
{
    protected $model = ConversationMessage::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'conversation_id' => Conversation::factory(),
            'direction' => ConversationMessage::DIRECTION_INBOUND,
            'type' => 'text',
            'provider_message_id' => 'msg_'.Str::lower(Str::random(10)),
            'body' => fake()->sentence(),
            'status' => 'received',
            'sent_at' => now(),
            'meta' => [],
        ];
    }
}
