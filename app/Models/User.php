<?php

namespace App\Models;

use App\Concerns\HasRoles;
use App\Models\Concerns\BelongsToCompany;
use Database\Factories\UserFactory;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use App\Notifications\AccountActivationNotification;
use App\Notifications\PasswordResetNotification;

class User extends Authenticatable implements MustVerifyEmailContract
{
    /** @use HasFactory<UserFactory> */
    use BelongsToCompany, HasFactory, HasRoles, MustVerifyEmail, Notifiable;

    public const STATUSES = [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'suspended' => 'Suspended',
    ];

    protected $fillable = [
        'name',
        'email',
        'phone',
        'timezone',
        'language',
        'password',
        'photo_path',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Supported UI languages (future-ready localization).
     *
     * @var array<string, string>
     */
    public const LANGUAGES = [
        'en' => 'English',
        'es' => 'Spanish',
        'fr' => 'French',
        'de' => 'German',
        'pt' => 'Portuguese',
        'ar' => 'Arabic',
        'ur' => 'Urdu',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'dashboard_tour_completed_at' => 'datetime',
            'locked_until' => 'datetime',
            'password' => 'hashed',
            'is_super_admin' => 'boolean',
            'failed_login_attempts' => 'integer',
        ];
    }

    public function hasCompletedDashboardTour(): bool
    {
        return $this->dashboard_tour_completed_at !== null;
    }

    public function ownedCompanies(): HasMany
    {
        return $this->hasMany(Company::class, 'owner_id');
    }

    public function assignedLeads(): HasMany
    {
        return $this->hasMany(Lead::class, 'assigned_to');
    }

    public function assignedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function notificationPreferences(): HasMany
    {
        return $this->hasMany(UserNotificationPreference::class);
    }

    public function isSuperAdmin(): bool
    {
        return (bool) $this->is_super_admin;
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! filled($term)) {
            return $query;
        }

        return $query->where(function (Builder $builder) use ($term) {
            \App\Support\SearchTerm::whereEscaped($builder, 'name', $term);
            \App\Support\SearchTerm::whereEscaped($builder, 'email', $term, 'or');
        });
    }

    public function scopeRole(Builder $query, ?string $role): Builder
    {
        if (! filled($role)) {
            return $query;
        }

        $slug = $role === 'user' ? 'sales' : $role;

        return $query->whereHas('roles', fn (Builder $builder) => $builder->where('slug', $slug));
    }

    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        if (! filled($status)) {
            return $query;
        }

        return $query->where('status', $status);
    }

    public function photoUrl(): string
    {
        if ($this->photo_path && Storage::disk('public')->exists($this->photo_path)) {
            return '/storage/'.ltrim($this->photo_path, '/');
        }

        return 'https://ui-avatars.com/api/?name='.urlencode($this->name).'&background=007bff&color=fff&size=128';
    }

    public function adminlte_image(): string
    {
        return $this->photoUrl();
    }

    public function adminlte_profile_url(): string
    {
        return 'profile.edit';
    }

    public function updatePhoto(UploadedFile $file): void
    {
        $this->deletePhotoFile();

        $this->photo_path = $file->store('avatars', 'public');
        $this->save();
    }

    public function removePhoto(): void
    {
        $this->deletePhotoFile();

        $this->photo_path = null;
        $this->save();
    }

    public function deletePhotoFile(): void
    {
        if ($this->photo_path && Storage::disk('public')->exists($this->photo_path)) {
            Storage::disk('public')->delete($this->photo_path);
        }
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'active' => 'success',
            'inactive' => 'secondary',
            'suspended' => 'danger',
            default => 'secondary',
        };
    }

    public function roleBadgeClass(): string
    {
        return match (true) {
            $this->hasRole('admin') => 'primary',
            default => 'info',
        };
    }

    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new PasswordResetNotification($token));
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new AccountActivationNotification);
    }
}
