<?php

namespace App\Models;

use App\Enums\Channels\ChannelProvider;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadChannelMeta extends Model
{
    use BelongsToCompany;

    protected $table = 'lead_channel_meta';

    protected $fillable = [
        'company_id',
        'lead_id',
        'channel_connection_id',
        'channel_webhook_event_id',
        'provider',
        'campaign_id',
        'campaign_name',
        'adset_id',
        'ad_id',
        'ad_name',
        'form_id',
        'form_name',
        'page_id',
        'raw',
    ];

    protected function casts(): array
    {
        return [
            'provider' => ChannelProvider::class,
            'raw' => 'array',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function connection(): BelongsTo
    {
        return $this->belongsTo(ChannelConnection::class, 'channel_connection_id');
    }

    public function webhookEvent(): BelongsTo
    {
        return $this->belongsTo(ChannelWebhookEvent::class, 'channel_webhook_event_id');
    }
}
