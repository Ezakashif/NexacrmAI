<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LeadActivity extends Model
{
    use BelongsToCompany;
    use HasFactory;

    public const TYPES = [
        'call',
        'whatsapp',
        'email',
        'meeting',
        'note',
        'status_change',
    ];

    public const TYPE_LABELS = [
        'call' => 'Phone call',
        'whatsapp' => 'WhatsApp',
        'email' => 'Email',
        'meeting' => 'Meeting',
        'note' => 'Note',
        'status_change' => 'Status changed',
    ];

    public const TYPE_ICONS = [
        'call' => 'fas fa-phone',
        'whatsapp' => 'fab fa-whatsapp',
        'email' => 'fas fa-envelope',
        'meeting' => 'fas fa-calendar-check',
        'note' => 'fas fa-sticky-note',
        'status_change' => 'fas fa-exchange-alt',
    ];

    public const TYPE_COLORS = [
        'call' => 'primary',
        'whatsapp' => 'success',
        'email' => 'info',
        'meeting' => 'warning',
        'note' => 'secondary',
        'status_change' => 'purple',
    ];

    protected $fillable = [
        'lead_id',
        'user_id',
        'type',
        'summary',
        'occurred_at',
        'next_follow_up_date',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'next_follow_up_date' => 'date',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function typeLabel(): string
    {
        return self::TYPE_LABELS[$this->type] ?? ucfirst(str_replace('_', ' ', $this->type));
    }

    public function typeIcon(): string
    {
        return self::TYPE_ICONS[$this->type] ?? 'fas fa-circle';
    }

    public function typeColor(): string
    {
        return self::TYPE_COLORS[$this->type] ?? 'secondary';
    }

    public static function log(
        Lead $lead,
        string $type,
        ?string $summary = null,
        ?\DateTimeInterface $occurredAt = null,
        ?\DateTimeInterface $nextFollowUp = null,
        ?int $userId = null,
    ): self {
        $activity = self::create([
            'lead_id' => $lead->id,
            'user_id' => $userId ?? auth()->id(),
            'type' => $type,
            'summary' => $summary,
            'occurred_at' => $occurredAt ?? now(),
            'next_follow_up_date' => $nextFollowUp,
        ]);

        if ($activity->company_id === null && $lead->company_id !== null) {
            $activity->company_id = $lead->company_id;
            $activity->save();
        }

        if ($nextFollowUp !== null) {
            $lead->update(['follow_up_date' => $nextFollowUp]);
        }

        if ($lead->status === 'new' && in_array($type, ['call', 'whatsapp', 'email', 'meeting'], true)) {
            $lead->update(['status' => 'contacted']);
        }

        return $activity;
    }
}
