<?php

namespace App\Models;

use App\Enums\Channels\ChannelProvider;
use App\Models\Concerns\BelongsToCompany;
use Database\Factories\ChannelContactFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChannelContact extends Model
{
    /** @use HasFactory<ChannelContactFactory> */
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'company_id',
        'channel_connection_id',
        'lead_id',
        'customer_id',
        'provider',
        'external_user_id',
        'email',
        'phone',
        'display_name',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'provider' => ChannelProvider::class,
            'meta' => 'array',
        ];
    }

    public function connection(): BelongsTo
    {
        return $this->belongsTo(ChannelConnection::class, 'channel_connection_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }
}
