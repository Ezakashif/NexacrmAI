<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class EmailSendLog extends Model
{
    public const STATUS_QUEUED = 'queued';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    public const STATUS_PREVIEW = 'preview';

    public const STATUS_TEST = 'test';

    protected $fillable = [
        'email_template_id',
        'category',
        'locale',
        'to_email',
        'subject',
        'status',
        'mailer',
        'error_message',
        'placeholders',
        'triggered_by',
        'related_type',
        'related_id',
    ];

    protected function casts(): array
    {
        return [
            'placeholders' => 'array',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class, 'email_template_id');
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    public function related(): MorphTo
    {
        return $this->morphTo();
    }
}
