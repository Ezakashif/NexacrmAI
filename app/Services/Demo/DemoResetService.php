<?php

namespace App\Services\Demo;

use App\Models\ActivityLog;
use App\Models\ChannelConnection;
use App\Models\ChannelContact;
use App\Models\ChannelWebhookEvent;
use App\Models\Company;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadChannelMeta;
use App\Models\Role;
use App\Models\Scopes\CompanyScope;
use App\Models\Task;
use App\Models\User;
use App\Models\UserInvitation;
use App\Support\CurrentCompany;
use App\Support\DemoEnvironment;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Tenant-scoped reset for the Northstar live-demo company only.
 *
 * Deletion order (children before parents), all filtered by verified company_id:
 * 1. conversation_messages
 * 2. conversations
 * 3. channel_webhook_events
 * 4. channel_contacts
 * 5. lead_channel_meta
 * 6. channel_connections
 * 7. user_invitations
 * 8. email_send_logs
 * 9. notifications (notifiable users in tenant)
 * 10. user_notification_preferences
 * 11. lead_activities
 * 12. tasks
 * 13. activity_logs
 * 14. customers
 * 15. leads
 * 16. extra users (not seeded demo emails) + their avatar files
 * 17. extra roles (not admin/sales/sales_manager)
 * 18. sessions for remaining demo users
 * 19. DemoDataSeeder upsert
 *
 * Company logo is deleted only when companies.logo_path is set on this company row.
 */
class DemoResetService
{
    public function reset(): Company
    {
        DemoEnvironment::requireSeedPassword();

        $company = Company::withTrashed()->where('slug', DemoEnvironment::companySlug())->first();
        $company = DemoEnvironment::assertResettableCompany($company);

        if ($company->trashed()) {
            $company->restore();
        }

        $companyId = (int) $company->id;
        if ($companyId < 1) {
            throw new RuntimeException('Demo company id is invalid. Aborting.');
        }

        config(['tenancy.fail_closed_without_context' => false]);
        app(CurrentCompany::class)->set($company);

        $filePaths = $this->collectTenantFilePaths($company);

        DB::transaction(function () use ($companyId) {
            $this->deleteScoped($companyId);
        });

        foreach ($filePaths as $path) {
            $this->deletePublicPath($path);
        }

        app(DemoDataSeeder::class)->run();

        $fresh = Company::query()->where('slug', DemoDataSeeder::COMPANY_SLUG)->first();

        return DemoEnvironment::assertResettableCompany($fresh);
    }

    /**
     * @return list<string>
     */
    private function collectTenantFilePaths(Company $company): array
    {
        $paths = [];

        if (filled($company->logo_path)) {
            $paths[] = (string) $company->logo_path;
        }

        $photos = User::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $company->id)
            ->whereNotNull('photo_path')
            ->pluck('photo_path');

        foreach ($photos as $path) {
            $paths[] = (string) $path;
        }

        return array_values(array_unique(array_filter($paths)));
    }

    private function deletePublicPath(string $path): void
    {
        $path = ltrim($path, '/');
        if ($path === '' || str_contains($path, '..')) {
            return;
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    private function deleteScoped(int $companyId): void
    {
        $userIds = User::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $companyId)
            ->pluck('id')
            ->all();

        $this->deleteByCompany(ConversationMessage::class, $companyId);
        $this->deleteByCompany(Conversation::class, $companyId);
        $this->deleteByCompany(ChannelWebhookEvent::class, $companyId);
        $this->deleteByCompany(ChannelContact::class, $companyId);
        $this->deleteByCompany(LeadChannelMeta::class, $companyId);
        $this->deleteByCompany(ChannelConnection::class, $companyId);
        $this->deleteByCompany(UserInvitation::class, $companyId);

        if (Schema::hasTable('email_send_logs')) {
            if (Schema::hasColumn('email_send_logs', 'company_id')) {
                DB::table('email_send_logs')->where('company_id', $companyId)->delete();
            } elseif ($userIds !== [] && Schema::hasColumn('email_send_logs', 'triggered_by')) {
                DB::table('email_send_logs')->whereIn('triggered_by', $userIds)->delete();
            }
        }

        if ($userIds !== [] && Schema::hasTable('notifications')) {
            DB::table('notifications')
                ->where('notifiable_type', User::class)
                ->whereIn('notifiable_id', $userIds)
                ->delete();
        }

        if ($userIds !== [] && Schema::hasTable('user_notification_preferences')) {
            DB::table('user_notification_preferences')->whereIn('user_id', $userIds)->delete();
        }

        $this->deleteByCompany(LeadActivity::class, $companyId);
        $this->deleteByCompany(Task::class, $companyId);
        $this->deleteByCompany(ActivityLog::class, $companyId);
        $this->deleteByCompany(Customer::class, $companyId);
        $this->deleteByCompany(Lead::class, $companyId);

        $seededEmails = DemoEnvironment::seededEmails();

        $extraUsers = User::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $companyId)
            ->whereNotIn('email', $seededEmails)
            ->get();

        foreach ($extraUsers as $user) {
            $user->roles()->detach();
            $user->delete();
        }

        $protectedRoleSlugs = ['admin', 'sales', DemoDataSeeder::SALES_MANAGER_ROLE_SLUG];
        $extraRoles = Role::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $companyId)
            ->whereNotIn('slug', $protectedRoleSlugs)
            ->get();

        foreach ($extraRoles as $role) {
            $role->permissions()->detach();
            $role->users()->detach();
            $role->delete();
        }

        if ($userIds !== [] && Schema::hasTable('sessions') && Schema::hasColumn('sessions', 'user_id')) {
            DB::table('sessions')->whereIn('user_id', $userIds)->delete();
        }
    }

    /**
     * @param  class-string  $model
     */
    private function deleteByCompany(string $model, int $companyId): void
    {
        if (! class_exists($model)) {
            return;
        }

        $instance = new $model;
        $table = $instance->getTable();

        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'company_id')) {
            return;
        }

        $query = $model::withoutGlobalScope(CompanyScope::class)->where('company_id', $companyId);

        if (in_array('Illuminate\\Database\\Eloquent\\SoftDeletes', class_uses_recursive($model), true)) {
            $query->withTrashed();
        }

        $query->each(function ($record) {
            $record->forceDelete();
        }, 100);
    }
}
