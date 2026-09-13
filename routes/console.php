<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

$remindersEnabled = fn () => (bool) config('lead_reminders.enabled', true);
$dailyTime = config('lead_reminders.schedule_time', '08:00');

Schedule::command('leads:send-follow-up-reminders --tier=day_before')
    ->dailyAt($dailyTime)
    ->when($remindersEnabled)
    ->when(fn () => config('lead_reminders.tiers.day_before.enabled', true));

Schedule::command('leads:send-follow-up-reminders --tier=due')
    ->dailyAt($dailyTime)
    ->when($remindersEnabled)
    ->when(fn () => config('lead_reminders.tiers.due.enabled', true));

Schedule::command('leads:send-follow-up-reminders --tier=overdue')
    ->dailyAt($dailyTime)
    ->when($remindersEnabled)
    ->when(fn () => config('lead_reminders.tiers.overdue.enabled', true));

Schedule::command('leads:send-follow-up-reminders --tier=hours_before')
    ->hourly()
    ->when($remindersEnabled)
    ->when(fn () => config('lead_reminders.tiers.hours_before.enabled', true));

$taskRemindersEnabled = fn () => (bool) config('task_reminders.enabled', true);
$taskReminderTime = config('task_reminders.schedule_time', '08:15');

Schedule::command('tasks:send-reminders --tier=due')
    ->dailyAt($taskReminderTime)
    ->when($taskRemindersEnabled)
    ->when(fn () => config('task_reminders.tiers.due.enabled', true));

Schedule::command('tasks:send-reminders --tier=overdue')
    ->dailyAt($taskReminderTime)
    ->when($taskRemindersEnabled)
    ->when(fn () => config('task_reminders.tiers.overdue.enabled', true));

Schedule::call(function () {
    \App\Models\PlatformSetting::query()->updateOrCreate(
        ['key' => 'scheduler_last_run_at'],
        ['value' => now()->toIso8601String()],
    );
    \Illuminate\Support\Facades\Cache::forget(\App\Services\SuperAdmin\PlatformSettingsService::CACHE_KEY);
})->everyFiveMinutes()->name('platform-scheduler-heartbeat');

Schedule::command('platform:send-alert-notifications')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->name('platform-alert-notifications');

Schedule::command('trials:send-ending-notifications --days=3')
    ->dailyAt('09:00')
    ->name('trial-ending-notifications');

Schedule::command('activity-logs:prune --days=90')
    ->weekly()
    ->name('activity-logs-prune');

Schedule::command('notifications:prune')
    ->weekly()
    ->name('notifications-prune');

Schedule::command('demo:reset')
    ->dailyAt((string) config('demo.reset_time', '03:15'))
    ->when(fn () => (bool) config('demo.reset_enabled'))
    ->withoutOverlapping(120)
    ->name('demo-tenant-reset');
