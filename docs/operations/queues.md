# Queues

Background job processing for the CRM.

## Defaults

| Setting | Value |
|---|---|
| Connection | `database` (`.env` `QUEUE_CONNECTION`) |
| Channel webhooks queue | `channels` (`CHANNELS_WEBHOOK_QUEUE`) |
| Max attempts (channel config) | 5 |
| Tests | Forced `QUEUE_CONNECTION=sync` in `phpunit.xml` |

## Why workers matter

`WebhookIngestionService` dispatches `ProcessChannelWebhookJob` onto the `channels` queue. Without a worker:

- HTTP still returns `202 Accepted`
- Events stay `queued`
- Leads / Inbox messages do not appear

## Run locally

```bash
php artisan queue:work --queue=channels,default
```

Or via:

```bash
composer dev
```

(`queue:listen` is included in the concurrent `composer dev` script.)

## Production

Supervise a long-running worker (Supervisor / systemd). After deploys:

```bash
php artisan queue:restart
```

See [Deployment](../getting-started/deployment.md).

## Failed jobs

Inspect `failed_jobs` table / `php artisan queue:failed`.  
Channel events also store status + error on `channel_webhook_events` and connection health fields.

## Related queues / mail

- Auth emails send synchronously (no worker required).  
- Other notifications may queue depending on implementation — keep `default` in the worker list.

## Related

- [Channels overview](../channels/overview.md)
- [Troubleshooting](../troubleshooting.md)
