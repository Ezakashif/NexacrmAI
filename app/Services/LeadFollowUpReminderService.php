<?php

namespace App\Services;

use App\Jobs\SendLeadFollowUpReminderJob;
use App\Models\Company;
use App\Models\Lead;
use App\Models\User;
use App\Notifications\LeadFollowUpDue;
use App\Support\CurrentCompany;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class LeadFollowUpReminderService
{
    public const TIERS = ['day_before', 'hours_before', 'due', 'overdue'];

    /**
     * Dispatch reminder jobs for one tier (or all enabled tiers), per company.
     *
     * @return int Number of jobs dispatched
     */
    public function dispatchReminders(?string $tier = null): int
    {
        if (! config('lead_reminders.enabled')) {
            return 0;
        }

        $tiers = $tier ? [$tier] : self::TIERS;
        $dispatched = 0;
        $currentCompany = app(CurrentCompany::class);

        Company::query()
            ->orderBy('id')
            ->each(function (Company $company) use ($tiers, $currentCompany, &$dispatched): void {
                $currentCompany->set($company);

                try {
                    foreach ($tiers as $tierKey) {
                        $this->assertValidTier($tierKey);

                        if (! config("lead_reminders.tiers.{$tierKey}.enabled", true)) {
                            continue;
                        }

                        $dispatched += $this->dispatchTier($tierKey);
                    }
                } finally {
                    $currentCompany->clear();
                }
            });

        return $dispatched;
    }

    /**
     * Deliver a single reminder synchronously (called by the queued job).
     * A row lock makes duplicate queued jobs harmless.
     */
    public function deliverReminder(int $leadId, string $tier): bool
    {
        $this->assertValidTier($tier);

        if (! config('lead_reminders.enabled') || ! config("lead_reminders.tiers.{$tier}.enabled", true)) {
            return false;
        }

        return DB::transaction(function () use ($leadId, $tier): bool {
            $lead = Lead::withoutCompanyScope()
                ->lockForUpdate()
                ->find($leadId);

            if (! $lead || ! $this->isEligible($lead, $tier)) {
                return false;
            }

            $assignee = User::withoutCompanyScope()
                ->where('company_id', $lead->company_id)
                ->find($lead->assigned_to);

            if (! $assignee || $assignee->status !== 'active' || $assignee->is_super_admin) {
                return false;
            }

            $notification = new LeadFollowUpDue($lead, $tier);

            if ($notification->via($assignee) !== []) {
                $assignee->notifyNow($notification);
            }

            $lead->markFollowUpReminderSent($tier);

            return true;
        });
    }

    protected function dispatchTier(string $tier): int
    {
        $dispatched = 0;

        Lead::query()
            ->eligibleForFollowUpReminderTier($tier)
            ->chunkById(50, function ($leads) use ($tier, &$dispatched) {
                foreach ($leads as $lead) {
                    if ($tier === 'hours_before' && ! $this->isInsideHoursBeforeWindow($lead)) {
                        continue;
                    }

                    SendLeadFollowUpReminderJob::dispatch($lead->id, $tier);
                    $dispatched++;
                }
            });

        return $dispatched;
    }

    public function isEligible(Lead $lead, string $tier): bool
    {
        if (! $lead->assigned_to || ! $lead->follow_up_date) {
            return false;
        }

        if (in_array($lead->status, ['won', 'lost'], true)) {
            return false;
        }

        if ($tier !== 'overdue' && $lead->hasFollowUpReminderBeenSent($tier)) {
            return false;
        }

        return match ($tier) {
            'day_before' => $lead->follow_up_date->isSameDay(today()->addDay()),
            'hours_before' => $lead->follow_up_date->isSameDay(today())
                && $this->isInsideHoursBeforeWindow($lead),
            'due' => $lead->follow_up_date->lessThanOrEqualTo(today()),
            'overdue' => $lead->follow_up_date->isBefore(today())
                && $lead->hasFollowUpReminderBeenSent('due')
                && $this->overdueReminderIsDue($lead),
            default => false,
        };
    }

    public function isInsideHoursBeforeWindow(Lead $lead): bool
    {
        if (! $lead->follow_up_date || ! $lead->follow_up_date->isSameDay(today())) {
            return false;
        }

        $hours = (int) config('lead_reminders.tiers.hours_before.hours', 2);
        $defaultTime = (string) config('lead_reminders.default_follow_up_time', '09:00');

        $followUpAt = Carbon::parse($lead->follow_up_date->toDateString().' '.$defaultTime);

        $windowStart = $followUpAt->copy()->subHours($hours);
        $windowEnd = $windowStart->copy()->addHour();

        return now()->betweenIncluded($windowStart, $windowEnd);
    }

    private function overdueReminderIsDue(Lead $lead): bool
    {
        $lastSent = $lead->followUpReminderSentAt('overdue');

        if (! $lastSent) {
            return true;
        }

        $repeatDays = max(1, (int) config('lead_reminders.tiers.overdue.repeat_days', 1));

        return $lastSent->copy()->addDays($repeatDays)->lessThanOrEqualTo(now());
    }

    protected function assertValidTier(string $tier): void
    {
        if (! in_array($tier, self::TIERS, true)) {
            throw new InvalidArgumentException("Unknown follow-up reminder tier [{$tier}].");
        }
    }
}
