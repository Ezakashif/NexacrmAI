<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Notifications\DatabaseNotification;

class PruneNotifications extends Command
{
    protected $signature = 'notifications:prune {--days= : Override the configured retention period}';

    protected $description = 'Prune read database notifications outside the retention period';

    public function handle(): int
    {
        $days = max(1, (int) ($this->option('days') ?? config('notifications.retention_days', 90)));
        $deleted = DatabaseNotification::query()
            ->whereNotNull('read_at')
            ->where('read_at', '<', now()->subDays($days))
            ->delete();

        $this->info("Pruned {$deleted} read notification(s).");

        return self::SUCCESS;
    }
}
