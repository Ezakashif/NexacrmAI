<?php

namespace App\Console\Commands;

use App\Services\TaskReminderService;
use Illuminate\Console\Command;

class SendTaskReminders extends Command
{
    protected $signature = 'tasks:send-reminders
                            {--tier= : Reminder tier (due, overdue). Omit to run all enabled tiers.}';

    protected $description = 'Queue due and overdue task reminders for assigned users';

    public function handle(TaskReminderService $service): int
    {
        $tier = $this->option('tier');

        if (is_string($tier) && $tier !== '' && ! in_array($tier, TaskReminderService::TIERS, true)) {
            $this->error("Invalid tier [{$tier}]. Allowed: ".implode(', ', TaskReminderService::TIERS));

            return self::FAILURE;
        }

        $dispatched = $service->dispatchReminders($tier ?: null);
        $this->info("Dispatched {$dispatched} task reminder job(s) (".($tier ?: 'all enabled tiers').').');

        return self::SUCCESS;
    }
}
