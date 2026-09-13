<?php

namespace App\Models;

use Database\Factories\ContactInquiryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactInquiry extends Model
{
    /** @use HasFactory<ContactInquiryFactory> */
    use HasFactory;

    public const STATUS_NEW = 'new';

    public const STATUS_REVIEWED = 'reviewed';

    public const STATUS_CLOSED = 'closed';

    public const STATUSES = [
        self::STATUS_NEW => 'New',
        self::STATUS_REVIEWED => 'Reviewed',
        self::STATUS_CLOSED => 'Closed',
    ];

    protected $fillable = [
        'name',
        'email',
        'company',
        'phone',
        'message',
        'intent',
        'status',
        'reviewed_at',
        'reviewed_by',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isNew(): bool
    {
        return $this->status === self::STATUS_NEW;
    }

    public function isDemo(): bool
    {
        return $this->intent === 'demo';
    }

    public function intentLabel(): string
    {
        return match ($this->intent) {
            'demo' => 'Demo request',
            null, '' => 'General',
            default => ucfirst(str_replace(['_', '-'], ' ', $this->intent)),
        };
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }

    public function markReviewed(?User $actor = null): void
    {
        if ($this->status !== self::STATUS_NEW) {
            return;
        }

        $this->forceFill([
            'status' => self::STATUS_REVIEWED,
            'reviewed_at' => now(),
            'reviewed_by' => $actor?->id,
        ])->save();
    }

    public function scopeNew(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_NEW);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! filled($term)) {
            return $query;
        }

        $like = '%'.$term.'%';

        return $query->where(function (Builder $builder) use ($like) {
            $builder->where('name', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('company', 'like', $like)
                ->orWhere('phone', 'like', $like)
                ->orWhere('message', 'like', $like);
        });
    }
}
