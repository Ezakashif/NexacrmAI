<?php

namespace App\Services;

use App\Jobs\SendTaskReminderJob;
use App\Models\Company;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskDue;
use App\Support\CurrentCompany;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TaskReminderService
{
    public const TIERS = ['due', 'overdue'];

    /**
     * Queue task reminders for one tier (or all enabled tiers), company by company.
     */
    public function dispatchReminders(?string $tier = null): int
    {
        if (! config('task_reminders.enabled')) {
            return 0;
        }

        $tiers = $tier ? [$tier] : self::TIERS;
        $dispatched = 0;
        $currentCompany = app(CurrentCompany::class);

        Company::query()->orderBy('id')->each(function (Company $company) use ($tiers, $currentCompany, &$dispatched): void {
            $currentCompany->set($company);

            try {
                foreach ($tiers as $tierKey) {
                    $this->assertValidTier($tierKey);

                    if (config("task_reminders.tiers.{$tierKey}.enabled", true)) {
                        $dispatched += $this->dispatchTier($tierKey);
                    }
                }
            } finally {
                $currentCompany->clear();
            }
        });

        return $dispatched;
    }

    /**
     * Deliver one reminder. A row lock makes duplicate queued jobs harmless.
     */
    public function deliverReminder(int $taskId, string $tier): bool
    {
        $this->assertValidTier($tier);

        if (! config('task_reminders.enabled') || ! config("task_reminders.tiers.{$tier}.enabled", true)) {
            return false;
        }

        return DB::transaction(function () use ($taskId, $tier): bool {
            $task = Task::withoutCompanyScope()
                ->lockForUpdate()
                ->find($taskId);

            if (! $task || ! $this->isEligible($task, $tier)) {
                return false;
            }

            $assignee = User::withoutCompanyScope()
                ->where('company_id', $task->company_id)
                ->find($task->assigned_to);

            if (! $assignee
                || $assignee->status !== 'active'
                || $assignee->is_super_admin
                || $assignee->company_id !== $task->company_id) {
                return false;
            }

            $notification = new TaskDue($task, $tier);

            // An opted-out reminder is intentionally considered processed so it
            // cannot result in repeated evaluation or a delayed surprise alert.
            if ($notification->via($assignee) !== []) {
                $assignee->notifyNow($notification);
            }

            $task->markReminderSent($tier);

            return true;
        });
    }

    public function isEligible(Task $task, string $tier): bool
    {
        if (! $task->assigned_to
            || ! $task->due_date
            || ! in_array($task->status, ['pending', 'in_progress'], true)) {
            return false;
        }

        return match ($tier) {
            'due' => ! $task->hasReminderBeenSent('due')
                && $task->due_date->isSameDay(today()),
            'overdue' => $task->due_date->isBefore(today())
                && $this->overdueReminderIsDue($task),
            default => false,
        };
    }

    private function dispatchTier(string $tier): int
    {
        $dispatched = 0;

        Task::query()
            ->whereNotNull('assigned_to')
            ->whereNotNull('due_date')
            ->whereIn('status', ['pending', 'in_progress'])
            ->when(
                $tier === 'due',
                fn ($query) => $query->whereDate('due_date', today()),
                fn ($query) => $query->whereDate('due_date', '<', today()),
            )
            ->chunkById(50, function ($tasks) use ($tier, &$dispatched): void {
                foreach ($tasks as $task) {
                    if ($this->isEligible($task, $tier)) {
                        SendTaskReminderJob::dispatch($task->id, $tier);
                        $dispatched++;
                    }
                }
            });

        return $dispatched;
    }

    private function overdueReminderIsDue(Task $task): bool
    {
        $lastSent = $task->reminderSentAt('overdue');

        if (! $lastSent) {
            return true;
        }

        $repeatDays = max(1, (int) config('task_reminders.tiers.overdue.repeat_days', 1));

        return $lastSent->copy()->addDays($repeatDays)->lessThanOrEqualTo(now());
    }

    private function assertValidTier(string $tier): void
    {
        if (! in_array($tier, self::TIERS, true)) {
            throw new InvalidArgumentException("Unknown task reminder tier [{$tier}].");
        }
    }
}
