<?php

namespace App\Models;

use App\Enums\Channels\ChannelProvider;
use App\Models\Concerns\BelongsToCompany;
use Database\Factories\ConversationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    /** @use HasFactory<ConversationFactory> */
    use BelongsToCompany, HasFactory;

    public const STATUS_OPEN = 'open';

    public const STATUS_CLOSED = 'closed';

    public const STATUS_PENDING = 'pending';

    protected $fillable = [
        'company_id',
        'channel_connection_id',
        'channel_contact_id',
        'lead_id',
        'customer_id',
        'assigned_to',
        'provider',
        'external_thread_id',
        'status',
        'subject',
        'last_message_at',
        'last_inbound_at',
        'unread_count',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'provider' => ChannelProvider::class,
            'last_message_at' => 'datetime',
            'last_inbound_at' => 'datetime',
            'unread_count' => 'integer',
            'meta' => 'array',
        ];
    }

    public function connection(): BelongsTo
    {
        return $this->belongsTo(ChannelConnection::class, 'channel_connection_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(ChannelContact::class, 'channel_contact_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ConversationMessage::class);
    }
}
