# Scheduler

Laravel scheduler entrypoint: `routes/console.php`.

## Cron requirement

```cron
* * * * * cd /path/to/crm && php artisan schedule:run >> /dev/null 2>&1
```

Without cron, reminders and pruning will not run.

## Scheduled tasks

| Schedule | Command / job | Notes |
|---|---|---|
| Daily @ lead reminder time (default 08:00) | `leads:send-follow-up-reminders --tier=day_before\|due\|overdue` | Gated by `lead_reminders` config |
| Hourly | `leads:send-follow-up-reminders --tier=hours_before` | Gated |
| Daily @ task reminder time (default 08:15) | `tasks:send-reminders --tier=due\|overdue` | Gated by `task_reminders` |
| Every 5 minutes | Platform scheduler heartbeat | Sets `scheduler_last_run_at` |
| Every 15 minutes | `platform:send-alert-notifications` | `withoutOverlapping` |
| Daily 09:00 | `trials:send-ending-notifications --days=3` | Trial ending notices |
| Weekly | `activity-logs:prune --days=90` | Retention |
| Weekly | `notifications:prune` | Retention |

## Manual commands (not all scheduled)

| Command | Purpose |
|---|---|
| `permissions:sync` | Sync RBAC registry |
| `platform:optimize-logo` | Logo optimization |
| `companies:release-deleted-identifiers` | Soft-delete identifier cleanup |

## Verifying the scheduler

1. Ensure cron is installed.  
2. Check Super Admin / platform setting `scheduler_last_run_at` updates roughly every 5 minutes.  
3. Temporarily run `php artisan schedule:run` and watch logs.

## Related env

See [Configuration](../getting-started/configuration.md) reminder section.

## Related

- [Deployment](../getting-started/deployment.md)
- [Queues](queues.md)
