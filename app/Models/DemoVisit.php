<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DemoVisit extends Model
{
    public const STATUS_NEW = 'new';

    public const STATUS_REVIEWED = 'reviewed';

    public const STATUS_CLOSED = 'closed';

    public const STATUSES = [
        self::STATUS_NEW => 'New',
        self::STATUS_REVIEWED => 'Reviewed',
        self::STATUS_CLOSED => 'Closed',
    ];

    protected $fillable = [
        'email',
        'name',
        'company',
        'country',
        'persona',
        'contact_consent',
        'is_anonymous',
        'status',
        'started_at',
        'reviewed_at',
        'reviewed_by',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'contact_consent' => 'boolean',
            'is_anonymous' => 'boolean',
            'started_at' => 'datetime',
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

    public function isAnonymous(): bool
    {
        return (bool) $this->is_anonymous || blank($this->email);
    }

    public function displayName(): string
    {
        if (filled($this->name)) {
            return (string) $this->name;
        }

        if (filled($this->email)) {
            return (string) $this->email;
        }

        return 'Anonymous visitor';
    }

    public function personaLabel(): string
    {
        $personas = (array) config('demo.personas', []);

        return (string) ($personas[$this->persona]['label'] ?? ucfirst(str_replace('_', ' ', (string) $this->persona)));
    }

    public function countryLabel(): ?string
    {
        return \App\Support\CountryNames::name($this->country);
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

    public function scopeIdentified(Builder $query): Builder
    {
        return $query->where('is_anonymous', false)->whereNotNull('email');
    }

    public function scopeAnonymous(Builder $query): Builder
    {
        return $query->where(function (Builder $builder) {
            $builder->where('is_anonymous', true)->orWhereNull('email');
        });
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
                ->orWhere('country', 'like', $like)
                ->orWhere('persona', 'like', $like);
        });
    }
}
