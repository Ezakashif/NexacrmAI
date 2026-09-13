<?php

namespace App\Models;

use App\Enums\Channels\ChannelConnectionStatus;
use App\Enums\Channels\ChannelProvider;
use App\Models\Concerns\BelongsToCompany;
use Database\Factories\ChannelConnectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ChannelConnection extends Model
{
    /** @use HasFactory<ChannelConnectionFactory> */
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'company_id',
        'uuid',
        'provider',
        'name',
        'status',
        'external_account_id',
        'external_page_id',
        'access_token',
        'refresh_token',
        'webhook_secret',
        'verify_token',
        'token_expires_at',
        'last_sync_at',
        'last_event_at',
        'last_error_at',
        'error_count',
        'last_error_message',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'provider' => ChannelProvider::class,
            'status' => ChannelConnectionStatus::class,
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'webhook_secret' => 'encrypted',
            'token_expires_at' => 'datetime',
            'last_sync_at' => 'datetime',
            'last_event_at' => 'datetime',
            'last_error_at' => 'datetime',
            'error_count' => 'integer',
            'meta' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $connection): void {
            if (! filled($connection->uuid)) {
                $connection->uuid = (string) Str::uuid();
            }
        });
    }

    public function webhookEvents(): HasMany
    {
        return $this->hasMany(ChannelWebhookEvent::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(ChannelContact::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function markEventReceived(): void
    {
        $this->forceFill([
            'last_event_at' => now(),
        ])->save();
    }

    public function markHealthy(): void
    {
        $this->forceFill([
            'status' => ChannelConnectionStatus::Connected,
            'error_count' => 0,
            'last_error_at' => null,
            'last_error_message' => null,
            'last_sync_at' => now(),
        ])->save();
    }

    public function markError(string $message): void
    {
        $this->forceFill([
            'status' => ChannelConnectionStatus::Error,
            'error_count' => $this->error_count + 1,
            'last_error_at' => now(),
            'last_error_message' => Str::limit($message, 1000, ''),
        ])->save();
    }
}
