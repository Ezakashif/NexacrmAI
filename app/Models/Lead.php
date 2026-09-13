<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use BelongsToCompany;
    use HasFactory;
    use SoftDeletes;

    public const BOARD_CARD_LIMIT = 300;

    public const SOURCES = [
        'website',
        'facebook',
        'referral',
        'whatsapp',
        'linkedin',
        'cold_call',
    ];

    public const STATUSES = [
        'new' => 'New',
        'contacted' => 'Contacted',
        'qualified' => 'Qualified',
        'proposal_sent' => 'Proposal Sent',
        'won' => 'Won',
        'lost' => 'Lost',
    ];

    /**
     * Statuses that can be set directly (won is only via convertToCustomer).
     *
     * @return array<string, string>
     */
    public static function manuallyAssignableStatuses(?string $currentStatus = null): array
    {
        $statuses = self::STATUSES;

        if ($currentStatus !== 'won') {
            unset($statuses['won']);
        }

        return $statuses;
    }

    /**
     * Whether this lead may transition to the given status without conversion.
     */
    public function canManuallyTransitionTo(string $status): bool
    {
        if (! array_key_exists($status, self::STATUSES)) {
            return false;
        }

        if ($status === 'won' && $this->status !== 'won') {
            return false;
        }

        return true;
    }

    protected $fillable = [
        'created_by',
        'assigned_to',
        'name',
        'email',
        'phone',
        'company',
        'source',
        'status',
        'sort_order',
        'estimated_value',
        'notes',
        'follow_up_date',
        'follow_up_reminders_sent',
    ];

    protected function casts(): array
    {
        return [
            'follow_up_date' => 'date',
            'follow_up_reminders_sent' => 'array',
            'estimated_value' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (Lead $lead) {
            if ($lead->isDirty('follow_up_date') || $lead->isDirty('assigned_to')) {
                $lead->follow_up_reminders_sent = null;
            }
        });
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(LeadActivity::class)->orderByDesc('occurred_at');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst(str_replace('_', ' ', $this->status));
    }

    public function whatsAppUrl(): ?string
    {
        if (! filled($this->phone)) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $this->phone);

        return filled($digits) ? 'https://wa.me/'.$digits : null;
    }

    public function callUrl(): ?string
    {
        if (! filled($this->phone)) {
            return null;
        }

        $digits = preg_replace('/[^\d+]/', '', $this->phone);

        return filled($digits) ? 'tel:'.$digits : null;
    }

    public function emailUrl(): ?string
    {
        return filled($this->email) ? 'mailto:'.$this->email : null;
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! filled($term)) {
            return $query;
        }

        return $query->where(function (Builder $builder) use ($term) {
            \App\Support\SearchTerm::whereEscaped($builder, 'name', $term);
            \App\Support\SearchTerm::whereEscaped($builder, 'email', $term, 'or');
            \App\Support\SearchTerm::whereEscaped($builder, 'phone', $term, 'or');
            \App\Support\SearchTerm::whereEscaped($builder, 'company', $term, 'or');
        });
    }

    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        if (! filled($status)) {
            return $query;
        }

        return $query->where('status', $status);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->canViewAllLeads()) {
            return $query;
        }

        return $query->where($query->getModel()->getTable().'.assigned_to', $user->id);
    }

    public function scopeAssignedTo(Builder $query, ?string $userId): Builder
    {
        if (! filled($userId)) {
            return $query;
        }

        if ($userId === 'unassigned') {
            return $query->whereNull('assigned_to');
        }

        return $query->where('assigned_to', $userId);
    }

    public function scopeSource(Builder $query, ?string $source): Builder
    {
        if (! filled($source)) {
            return $query;
        }

        return $query->where('source', $source);
    }

    public function scopeDueForFollowUpReminder(Builder $query): Builder
    {
        return $query->eligibleForFollowUpReminderTier('due');
    }

    public function scopeEligibleForFollowUpReminderTier(Builder $query, string $tier): Builder
    {
        $query
            ->whereNotNull('assigned_to')
            ->whereNotNull('follow_up_date')
            ->whereNotIn('status', ['won', 'lost'])
            ->whereHas('assignee', fn (Builder $assignee) => $assignee->where('status', 'active'));

        if ($tier !== 'overdue') {
            $query->where(function (Builder $builder) use ($tier) {
                $builder->whereNull('follow_up_reminders_sent')
                    ->orWhereNull("follow_up_reminders_sent->{$tier}");
            });
        }

        return match ($tier) {
            'day_before' => $query->whereDate('follow_up_date', today()->addDay()),
            'hours_before' => $query->whereDate('follow_up_date', today()),
            'due' => $query->whereDate('follow_up_date', '<=', today()),
            'overdue' => $query->whereDate('follow_up_date', '<', today()),
            default => $query->whereRaw('1 = 0'),
        };
    }

    public function hasFollowUpReminderBeenSent(string $tier): bool
    {
        $sent = $this->follow_up_reminders_sent ?? [];

        return filled($sent[$tier] ?? null);
    }

    public function markFollowUpReminderSent(string $tier): void
    {
        $sent = $this->follow_up_reminders_sent ?? [];
        $sent[$tier] = now()->toIso8601String();

        $this->forceFill(['follow_up_reminders_sent' => $sent])->save();
    }

    public function followUpReminderSentAt(string $tier): ?\Carbon\CarbonInterface
    {
        $value = ($this->follow_up_reminders_sent ?? [])[$tier] ?? null;

        return filled($value) ? \Carbon\Carbon::parse($value) : null;
    }
}
