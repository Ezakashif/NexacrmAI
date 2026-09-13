<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Gate;

class ActivityLog extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'user_id',
        'action',
        'subject_type',
        'subject_id',
        'properties',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
        ];
    }

    public const ACTION_LABELS = [
        'user.created' => 'User created',
        'user.updated' => 'User updated',
        'user.status_changed' => 'User status changed',
        'user.deleted' => 'User deleted',
        'user.login' => 'User logged in',
        'user.logout' => 'User logged out',
        'user.login_failed' => 'Login failed',
        'user.locked_out' => 'Account locked out',
        'password.reset_requested' => 'Password reset requested',
        'password.reset' => 'Password reset completed',
        'password.updated' => 'Password updated',
        'email.verified' => 'Email verified',
        'email.verification_resent' => 'Verification email resent',
        'email_template.created' => 'Email template created',
        'email_template.updated' => 'Email template updated',
        'email_template.deleted' => 'Email template deleted',
        'email_template.test_sent' => 'Email template test sent',
        'user.invitation_sent' => 'User invitation sent',
        'user.invitation_accepted' => 'User invitation accepted',
        'company.trial_ending_notified' => 'Trial ending notification sent',
        'session.revoked' => 'Session revoked',
        'session.revoked_others' => 'Other sessions revoked',
        'profile.updated' => 'Profile updated',
        'profile.photo_updated' => 'Profile photo updated',
        'profile.photo_removed' => 'Profile photo removed',
        'customer.created' => 'Customer created',
        'customer.updated' => 'Customer updated',
        'customer.deleted' => 'Customer deleted',
        'lead.created' => 'Lead created',
        'lead.updated' => 'Lead updated',
        'lead.deleted' => 'Lead deleted',
        'lead.assigned' => 'Lead assigned',
        'lead.converted' => 'Lead converted',
        'lead.status_changed' => 'Lead status changed',
        'lead.created_via_website' => 'Lead created via website',
        'lead.created_via_channel' => 'Lead created via channel',
        'lead.activity_logged' => 'Lead activity logged',
        'channel.webhook_received' => 'Channel webhook received',
        'channel.webhook_processed' => 'Channel webhook processed',
        'channel.webhook_failed' => 'Channel webhook failed',
        'channel.lead_processed' => 'Channel lead processed',
        'channel.message_received' => 'Channel message received',
        'channel.message_sent' => 'Channel message sent',
        'channel.conversation_assigned' => 'Conversation assigned',
        'channel.conversation_status_updated' => 'Conversation status updated',
        'channel.connection_updated' => 'Channel connection updated',
        'channel.connection_created' => 'Channel connection created',
        'channel.connection_tested' => 'Channel connection tested',
        'channel.connection_synced' => 'Channel connection synced',
        'channel.connection_retry' => 'Channel connection retry',
        'channel.connection_disconnected' => 'Channel connection disconnected',
        'channel.connection_secret_rotated' => 'Channel webhook secret rotated',
        'task.created' => 'Task created',
        'task.updated' => 'Task updated',
        'task.deleted' => 'Task deleted',
        'task.assigned' => 'Task assigned',
        'task.status_changed' => 'Task status changed',
        'company.created' => 'Company created',
        'company.updated' => 'Company updated',
        'company.status_changed' => 'Company status changed',
        'company.settings_updated' => 'Company settings updated',
        'company.deleted' => 'Company deleted',
        'company.restored' => 'Company restored',
        'plan.created' => 'Subscription plan created',
        'plan.updated' => 'Subscription plan updated',
        'plan.deleted' => 'Subscription plan deleted',
        'plan.duplicated' => 'Subscription plan duplicated',
        'plan.status_changed' => 'Subscription plan status changed',
        'plan.imported' => 'Subscription plans imported',
        'impersonation.started' => 'Impersonation started',
        'impersonation.ended' => 'Impersonation ended',
        'platform.settings_updated' => 'Platform settings updated',
        'platform.announcement_updated' => 'Announcement updated',
        'platform.super_admin_created' => 'Super Admin created',
    ];

    /**
     * @var array<class-string<Model>, string>
     */
    private const SUBJECT_SHOW_ROUTES = [
        Lead::class => 'leads.show',
        Customer::class => 'customers.show',
        Task::class => 'tasks.show',
        User::class => 'users.show',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Tenant CRM audit trail — excludes Super Admin / platform events.
     */
    public function scopeForTenant(Builder $query): Builder
    {
        return $query
            ->whereNotNull($query->getModel()->getTable().'.company_id')
            ->where(function (Builder $builder) {
                $builder->whereNull('user_id')
                    ->orWhereNotIn('user_id', User::withoutCompanyScope()
                        ->where('is_super_admin', true)
                        ->select('id'));
            });
    }

    /**
     * Super Admin / platform audit trail only.
     */
    public function scopeForPlatform(Builder $query): Builder
    {
        return $query->where(function (Builder $builder) {
            $builder->whereNull('company_id')
                ->orWhereIn('user_id', User::withoutCompanyScope()
                    ->where('is_super_admin', true)
                    ->select('id'));
        });
    }

    public function relatedCompanyName(): string
    {
        return $this->company?->name
            ?? ($this->properties['company_name'] ?? null)
            ?? ($this->properties['name'] ?? null)
            ?? 'Platform';
    }

    public function module(): string
    {
        return explode('.', (string) $this->action)[0] ?: 'system';
    }

    public function actionLabel(): string
    {
        return self::ACTION_LABELS[$this->action] ?? ucfirst(str_replace('.', ' ', $this->action));
    }

    /**
     * URL to the subject's detail page when it still exists and the viewer may open it.
     */
    public function subjectShowUrl(?User $viewer = null): ?string
    {
        $viewer ??= auth()->user();
        $subject = $this->relationLoaded('subject') ? $this->getRelation('subject') : $this->subject;

        if (! $viewer || ! $subject instanceof Model) {
            return null;
        }

        if ($subject instanceof User && str_starts_with((string) $this->action, 'profile.')) {
            if ($viewer->id === $subject->id) {
                return route('profile.edit');
            }

            return Gate::forUser($viewer)->allows('view', $subject)
                ? route('users.show', $subject)
                : null;
        }

        $routeName = self::SUBJECT_SHOW_ROUTES[$subject::class] ?? null;

        if (! $routeName || ! Gate::forUser($viewer)->allows('view', $subject)) {
            return null;
        }

        return route($routeName, $subject);
    }

    public function description(): string
    {
        $properties = $this->properties ?? [];

        return match ($this->action) {
            'user.status_changed' => sprintf(
                'Status changed from %s to %s',
                ucfirst($properties['from'] ?? 'unknown'),
                ucfirst($properties['to'] ?? 'unknown')
            ),
            'user.created' => sprintf(
                'Created user %s (%s)',
                $properties['name'] ?? 'unknown',
                $properties['email'] ?? ''
            ),
            'user.updated' => sprintf(
                'Updated user %s',
                $properties['name'] ?? ($this->subject?->name ?? 'unknown')
            ),
            'user.deleted' => sprintf(
                'Deleted user %s (%s)',
                $properties['name'] ?? 'unknown',
                $properties['email'] ?? ''
            ),
            'profile.updated' => 'Updated profile information',
            'profile.photo_updated' => 'Uploaded a new profile photo',
            'profile.photo_removed' => 'Removed profile photo',
            'customer.created' => sprintf('Created customer %s', $properties['name'] ?? 'unknown'),
            'customer.updated' => sprintf('Updated customer %s', $properties['name'] ?? ($this->subject?->name ?? 'unknown')),
            'customer.deleted' => sprintf('Deleted customer %s', $properties['name'] ?? 'unknown'),
            'lead.created' => sprintf('Created lead %s', $properties['name'] ?? 'unknown'),
            'lead.updated' => sprintf('Updated lead %s', $properties['name'] ?? ($this->subject?->name ?? 'unknown')),
            'lead.deleted' => sprintf('Deleted lead %s', $properties['name'] ?? 'unknown'),
            'lead.assigned' => sprintf(
                'Assigned lead %s to %s',
                $properties['name'] ?? ($this->subject?->name ?? 'unknown'),
                $properties['to'] ?? 'Unassigned'
            ),
            'lead.converted' => sprintf('Converted lead %s to customer', $properties['name'] ?? 'unknown'),
            'lead.status_changed' => sprintf(
                'Lead status changed from %s to %s',
                $properties['from'] ?? 'unknown',
                $properties['to'] ?? 'unknown'
            ),
            'lead.created_via_website' => sprintf('Website lead %s created', $properties['name'] ?? ($this->subject?->name ?? 'unknown')),
            'lead.activity_logged' => sprintf(
                'Logged %s activity on lead %s',
                $properties['type'] ?? 'an',
                $properties['lead_name'] ?? 'unknown'
            ),
            'task.created' => sprintf('Created task %s', $properties['title'] ?? 'unknown'),
            'task.updated' => sprintf('Updated task %s', $properties['title'] ?? ($this->subject?->title ?? 'unknown')),
            'task.deleted' => sprintf('Deleted task %s', $properties['title'] ?? 'unknown'),
            'task.assigned' => sprintf('Assigned task %s', $properties['title'] ?? ($this->subject?->title ?? 'unknown')),
            'task.status_changed' => sprintf(
                'Task status changed from %s to %s',
                $properties['from'] ?? 'unknown',
                $properties['to'] ?? 'unknown'
            ),
            'company.created' => sprintf('Created company %s', $properties['name'] ?? ($this->subject?->name ?? 'unknown')),
            'company.updated' => sprintf('Updated company %s', $properties['name'] ?? ($this->subject?->name ?? 'unknown')),
            'company.status_changed' => sprintf(
                'Company status changed from %s to %s',
                $properties['from'] ?? 'unknown',
                $properties['to'] ?? 'unknown'
            ),
            'company.deleted' => sprintf('Deleted company %s', $properties['name'] ?? 'unknown'),
            'company.restored' => sprintf('Restored company %s', $properties['name'] ?? 'unknown'),
            'plan.created' => sprintf('Created subscription plan %s', $properties['name'] ?? 'unknown'),
            'plan.updated' => sprintf('Updated subscription plan %s', $properties['name'] ?? ($this->subject?->name ?? 'unknown')),
            'plan.deleted' => sprintf('Deleted subscription plan %s', $properties['name'] ?? 'unknown'),
            'plan.duplicated' => sprintf('Duplicated subscription plan %s', $properties['name'] ?? 'unknown'),
            'plan.status_changed' => sprintf('Marked subscription plan %s as %s', $properties['name'] ?? 'unknown', $properties['to'] ?? 'unknown'),
            'plan.imported' => sprintf('Imported %d subscription plan(s)', $properties['count'] ?? 0),
            'impersonation.started' => sprintf(
                'Started impersonating %s at %s',
                $this->subject?->name ?? 'user',
                $properties['company_name'] ?? 'company'
            ),
            'impersonation.ended' => 'Ended impersonation session',
            'platform.settings_updated' => 'Updated platform settings',
            'platform.announcement_updated' => 'Updated platform announcement',
            'platform.super_admin_created' => sprintf(
                'Created Super Admin %s',
                $properties['email'] ?? 'unknown'
            ),
            default => $this->actionLabel(),
        };
    }
}
