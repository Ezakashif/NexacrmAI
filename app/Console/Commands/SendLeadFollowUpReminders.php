<?php

namespace App\Console\Commands;

use App\Services\LeadFollowUpReminderService;
use Illuminate\Console\Command;

class SendLeadFollowUpReminders extends Command
{
    protected $signature = 'leads:send-follow-up-reminders
                            {--tier= : Reminder tier (day_before, hours_before, due). Omit to run all enabled tiers.}';

    protected $description = 'Queue follow-up reminder emails for assigned lead owners';

    public function handle(LeadFollowUpReminderService $service): int
    {
        $tier = $this->option('tier');

        if (is_string($tier) && $tier !== '' && ! in_array($tier, LeadFollowUpReminderService::TIERS, true)) {
            $this->error("Invalid tier [{$tier}]. Allowed: ".implode(', ', LeadFollowUpReminderService::TIERS));

            return self::FAILURE;
        }

        $dispatched = $service->dispatchReminders($tier ?: null);

        $label = $tier ?: 'all enabled tiers';
        $this->info("Dispatched {$dispatched} follow-up reminder job(s) ({$label}).");

        return self::SUCCESS;
    }
}
