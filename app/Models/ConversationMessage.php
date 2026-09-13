<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Database\Factories\ConversationMessageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversationMessage extends Model
{
    /** @use HasFactory<ConversationMessageFactory> */
    use BelongsToCompany, HasFactory;

    public const DIRECTION_INBOUND = 'inbound';

    public const DIRECTION_OUTBOUND = 'outbound';

    public const DIRECTION_INTERNAL = 'internal';

    protected $fillable = [
        'company_id',
        'conversation_id',
        'channel_contact_id',
        'user_id',
        'direction',
        'type',
        'provider_message_id',
        'body',
        'status',
        'sent_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(ChannelContact::class, 'channel_contact_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
