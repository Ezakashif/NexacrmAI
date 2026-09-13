<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Plan extends Model
{
    /** @use HasFactory<\Database\Factories\PlanFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'max_users',
        'max_leads',
        'max_customers',
        'price_cents',
        'short_description',
        'description',
        'monthly_price',
        'yearly_price',
        'currency',
        'billing_cycle',
        'trial_days',
        'is_free',
        'is_featured',
        'is_public',
        'sort_order',
        'notes',
        'is_default',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'monthly_price' => 'decimal:2',
            'yearly_price' => 'decimal:2',
            'max_users' => 'integer',
            'max_leads' => 'integer',
            'max_customers' => 'integer',
            'price_cents' => 'integer',
            'trial_days' => 'integer',
            'sort_order' => 'integer',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'is_free' => 'boolean',
            'is_featured' => 'boolean',
            'is_public' => 'boolean',
        ];
    }

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): ?string => $value === null ? null : Str::ucfirst($value),
        );
    }

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }

    public function features(): HasMany
    {
        return $this->hasMany(PlanFeature::class)->orderBy('sort_order')->orderBy('id');
    }

    public function limits(): HasMany
    {
        return $this->hasMany(PlanLimit::class)->orderBy('sort_order')->orderBy('id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public static function default(): ?self
    {
        return static::query()->where('is_default', true)->first()
            ?? static::query()->active()->orderBy('sort_order')->orderBy('id')->first();
    }

    public function isUnlimited(string $metric): bool
    {
        $limit = $this->limitFor($metric);

        return $limit ? $limit->isUnlimited() : $this->{"max_{$metric}"} === null;
    }

    public function limitFor(string $key): ?PlanLimit
    {
        if ($this->relationLoaded('limits')) {
            return $this->limits->firstWhere('limit_key', $key);
        }

        return $this->limits()->where('limit_key', $key)->first();
    }
}
