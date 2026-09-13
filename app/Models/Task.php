<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use BelongsToCompany;
    use HasFactory;
    use SoftDeletes;

    public const BOARD_CARD_LIMIT = 300;

    public const STATUSES = [
        'pending' => 'Pending',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];

    public const PRIORITIES = [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
        'urgent' => 'Urgent',
    ];

    protected $fillable = [
        'created_by',
        'assigned_to',
        'customer_id',
        'lead_id',
        'title',
        'description',
        'priority',
        'status',
        'sort_order',
        'due_date',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'datetime',
            'completed_at' => 'datetime',
            'reminders_sent' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (Task $task) {
            if ($task->isDirty('due_date') || $task->isDirty('assigned_to')) {
                $task->reminders_sent = null;
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

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function scopeVisibleTo($query, User $user)
    {
        if ($user->canViewAllTasks()) {
            return $query;
        }

        return $query->where($query->getModel()->getTable().'.assigned_to', $user->id);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! filled($term)) {
            return $query;
        }

        return $query->where(function (Builder $builder) use ($term) {
            \App\Support\SearchTerm::whereEscaped($builder, 'title', $term);
            \App\Support\SearchTerm::whereEscaped($builder, 'description', $term, 'or');
        });
    }

    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        if (! filled($status)) {
            return $query;
        }

        return $query->where('status', $status);
    }

    public function scopePriority(Builder $query, ?string $priority): Builder
    {
        if (! filled($priority)) {
            return $query;
        }

        return $query->where('priority', $priority);
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

    public function hasReminderBeenSent(string $tier): bool
    {
        return filled(($this->reminders_sent ?? [])[$tier] ?? null);
    }

    public function reminderSentAt(string $tier): ?\Carbon\CarbonInterface
    {
        $value = ($this->reminders_sent ?? [])[$tier] ?? null;

        return filled($value) ? Carbon::parse($value) : null;
    }

    public function markReminderSent(string $tier): void
    {
        $sent = $this->reminders_sent ?? [];
        $sent[$tier] = now()->toIso8601String();

        $this->forceFill(['reminders_sent' => $sent])->save();
    }
}
