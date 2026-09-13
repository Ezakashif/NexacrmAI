<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class EmailTemplate extends Model
{
    /** @use HasFactory<\Database\Factories\EmailTemplateFactory> */
    use HasFactory;

    protected $fillable = [
        'category',
        'slug',
        'locale',
        'name',
        'subject',
        'html_body',
        'text_body',
        'placeholders',
        'is_active',
        'use_branding',
        'version',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'placeholders' => 'array',
            'is_active' => 'boolean',
            'use_branding' => 'boolean',
            'version' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (EmailTemplate $template): void {
            if (blank($template->slug)) {
                $template->slug = Str::slug($template->category.'-'.$template->locale);
            }
        });
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function sendLogs(): HasMany
    {
        return $this->hasMany(EmailSendLog::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    public function scopeForLocale(Builder $query, string $locale): Builder
    {
        return $query->where('locale', $locale);
    }

    public function categoryLabel(): string
    {
        return (string) (config("email_templates.categories.{$this->category}.label") ?? Str::headline($this->category));
    }

    /**
     * @return array<string, string>
     */
    public function availablePlaceholders(): array
    {
        $configured = config("email_templates.categories.{$this->category}.placeholders", []);

        if (is_array($this->placeholders) && $this->placeholders !== []) {
            return array_replace($configured, $this->placeholders);
        }

        return is_array($configured) ? $configured : [];
    }
}
