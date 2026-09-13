<?php

namespace App\Models;

use App\Enums\Channels\ChannelProvider;
use App\Enums\Channels\WebhookEventStatus;
use App\Models\Concerns\BelongsToCompany;
use Database\Factories\ChannelWebhookEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ChannelWebhookEvent extends Model
{
    /** @use HasFactory<ChannelWebhookEventFactory> */
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'company_id',
        'channel_connection_id',
        'uuid',
        'provider',
        'event_type',
        'idempotency_key',
        'status',
        'attempts',
        'headers',
        'payload',
        'signature',
        'signature_valid',
        'error_message',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'provider' => ChannelProvider::class,
            'status' => WebhookEventStatus::class,
            'attempts' => 'integer',
            'headers' => 'array',
            'signature_valid' => 'boolean',
            'processed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $event): void {
            if (! filled($event->uuid)) {
                $event->uuid = (string) Str::uuid();
            }
        });
    }

    public function connection(): BelongsTo
    {
        return $this->belongsTo(ChannelConnection::class, 'channel_connection_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function decodedPayload(): array
    {
        $decoded = json_decode($this->payload, true);

        return is_array($decoded) ? $decoded : ['raw' => $this->payload];
    }

    public function markProcessing(): void
    {
        $this->forceFill([
            'status' => WebhookEventStatus::Processing,
            'attempts' => $this->attempts + 1,
        ])->save();
    }

    public function markProcessed(): void
    {
        $this->forceFill([
            'status' => WebhookEventStatus::Processed,
            'processed_at' => now(),
            'error_message' => null,
        ])->save();
    }

    public function markFailed(string $message): void
    {
        $this->forceFill([
            'status' => WebhookEventStatus::Failed,
            'error_message' => Str::limit($message, 5000, ''),
        ])->save();
    }

    public function markDuplicate(): void
    {
        $this->forceFill([
            'status' => WebhookEventStatus::Duplicate,
            'processed_at' => now(),
        ])->save();
    }

    public function markIgnored(?string $reason = null): void
    {
        $this->forceFill([
            'status' => WebhookEventStatus::Ignored,
            'processed_at' => now(),
            'error_message' => $reason,
        ])->save();
    }
}
