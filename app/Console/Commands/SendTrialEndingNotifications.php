<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\User;
use App\Notifications\TrialEndingNotification;
use App\Services\ActivityLogger;
use Illuminate\Console\Command;

class SendTrialEndingNotifications extends Command
{
    protected $signature = 'trials:send-ending-notifications {--days=3 : Days before trial end to notify}';

    protected $description = 'Email company owners when their trial is ending soon';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $targetDate = today()->addDays($days);
        $sent = 0;

        Company::query()
            ->where('subscription_status', Company::SUBSCRIPTION_TRIAL)
            ->whereDate('trial_ends_at', $targetDate)
            ->orderBy('id')
            ->each(function (Company $company) use ($days, &$sent): void {
                $owner = $company->owner_id
                    ? User::withoutCompanyScope()->active()->find($company->owner_id)
                    : null;

                if (! $owner || $owner->company_id !== $company->id) {
                    $owner = User::withoutCompanyScope()
                        ->active()
                        ->where('company_id', $company->id)
                        ->where('is_super_admin', false)
                        ->whereHas('roles', fn ($query) => $query
                            ->withoutCompanyScope()
                            ->where('slug', 'admin')
                            ->whereColumn('roles.company_id', 'users.company_id'))
                        ->orderBy('id')
                        ->first();
                }

                if (! $owner) {
                    return;
                }

                $owner->notify(new TrialEndingNotification($company, $days));

                ActivityLogger::log('company.trial_ending_notified', $company, [
                    'days_remaining' => $days,
                    'user_id' => $owner->id,
                    'email' => $owner->email,
                ], $owner->id);

                $sent++;
            });

        $this->info("Queued {$sent} trial-ending notification(s).");

        return self::SUCCESS;
    }
}
