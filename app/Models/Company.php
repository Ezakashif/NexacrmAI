<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    /** @use HasFactory<\Database\Factories\CompanyFactory> */
    use HasFactory;
    use SoftDeletes;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUSES = [
        self::STATUS_ACTIVE => 'Active',
        self::STATUS_SUSPENDED => 'Suspended',
    ];

    public const SUBSCRIPTION_TRIAL = 'trial';

    public const SUBSCRIPTION_ACTIVE = 'active';

    public const SUBSCRIPTION_EXPIRED = 'expired';

    public const SUBSCRIPTION_STATUSES = [
        self::SUBSCRIPTION_TRIAL => 'Trial',
        self::SUBSCRIPTION_ACTIVE => 'Active',
        self::SUBSCRIPTION_EXPIRED => 'Expired',
    ];

    public const DEFAULT_SLUG = 'default';

    protected $fillable = [
        'name',
        'slug',
        'email',
        'phone',
        'logo_path',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'postal_code',
        'country',
        'timezone',
        'currency',
        'business_hours',
        'owner_id',
        'plan_id',
        'status',
        'subscription_status',
        'trial_ends_at',
        'last_active_at',
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'last_active_at' => 'datetime',
            'business_hours' => 'array',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function leadActivities(): HasMany
    {
        return $this->hasMany(LeadActivity::class);
    }

    public function impersonationLogs(): HasMany
    {
        return $this->hasMany(ImpersonationLog::class);
    }

    public function isDefault(): bool
    {
        return $this->slug === self::DEFAULT_SLUG;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isOnTrial(): bool
    {
        return $this->subscription_status === self::SUBSCRIPTION_TRIAL
            && ($this->trial_ends_at === null || $this->trial_ends_at->isFuture());
    }

    public function isSubscriptionExpired(): bool
    {
        if ($this->subscription_status === self::SUBSCRIPTION_EXPIRED) {
            return true;
        }

        return $this->subscription_status === self::SUBSCRIPTION_TRIAL
            && $this->trial_ends_at !== null
            && $this->trial_ends_at->isPast();
    }

    public function isTrialExpired(): bool
    {
        return $this->subscription_status === self::SUBSCRIPTION_TRIAL
            && $this->trial_ends_at !== null
            && $this->trial_ends_at->isPast();
    }

    public function expiredAccessMessage(): string
    {
        if ($this->isTrialExpired()
            || ($this->subscription_status === self::SUBSCRIPTION_EXPIRED
                && $this->trial_ends_at !== null
                && $this->trial_ends_at->isPast())) {
            return 'Your free trial has expired.';
        }

        return 'Your company subscription has expired. Please contact support to renew access.';
    }

    public function logoUrl(): ?string
    {
        if (! filled($this->logo_path)) {
            return null;
        }

        return asset('storage/'.ltrim($this->logo_path, '/'));
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeSuspended(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SUSPENDED);
    }

    public function scopeOnTrial(Builder $query): Builder
    {
        return $query->where('subscription_status', self::SUBSCRIPTION_TRIAL)
            ->where(function (Builder $builder) {
                $builder->whereNull('trial_ends_at')
                    ->orWhere('trial_ends_at', '>', now());
            });
    }

    public function scopeSubscriptionExpired(Builder $query): Builder
    {
        return $query->where(function (Builder $builder) {
            $builder->where('subscription_status', self::SUBSCRIPTION_EXPIRED)
                ->orWhere(function (Builder $trialExpired) {
                    $trialExpired->where('subscription_status', self::SUBSCRIPTION_TRIAL)
                        ->whereNotNull('trial_ends_at')
                        ->where('trial_ends_at', '<=', now());
                });
        });
    }

    public function scopeInactiveForDays(Builder $query, int $days): Builder
    {
        $threshold = now()->subDays($days);

        return $query->where(function (Builder $builder) use ($threshold) {
            $builder->whereNull('last_active_at')
                ->orWhere('last_active_at', '<', $threshold);
        });
    }

    public static function default(): ?self
    {
        return static::query()->where('slug', self::DEFAULT_SLUG)->first();
    }
}
