<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use Illuminate\Console\Command;

class PruneActivityLogsCommand extends Command
{
    protected $signature = 'activity-logs:prune {--days=90 : Delete activity logs older than this many days}';

    protected $description = 'Prune old activity logs to control storage growth';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $threshold = now()->subDays($days);

        $deleted = ActivityLog::query()
            ->where('created_at', '<', $threshold)
            ->delete();

        $this->info("Deleted {$deleted} activity log(s) older than {$days} day(s).");

        return self::SUCCESS;
    }
}
