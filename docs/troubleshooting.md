# Troubleshooting

Common issues and fixes.

## Installation / boot

| Symptom | Likely cause | Fix |
|---|---|---|
| No application encryption key | Missing `APP_KEY` | `php artisan key:generate` |
| SQLite cannot open DB | File missing | Create `database/database.sqlite` |
| Mixed content / wrong links | `APP_URL` mismatch | Set `APP_URL` including port |
| Assets 404 | Frontend not built | `npm run build` |

## Permissions / menus

| Symptom | Fix |
|---|---|
| Channels / Inbox missing in sidebar | `php artisan permissions:sync` |
| 403 on expected page | Role lacks permission; check Roles UI / seeder |
| Sales can view but not manage channels | Expected — needs `manage.channels` |

## Tenancy

| Symptom | Fix |
|---|---|
| Empty lists in production artisan/tinker | Fail-closed without CurrentCompany — set company context |
| Cross-company data visible in tests | Create distinct companies explicitly on users |
| Soft-deleted company blocks email reuse | Run identifier release command / restore flow |

## Queues / channels

| Symptom | Fix |
|---|---|
| Webhook `202` but no lead | Start `php artisan queue:work --queue=channels,default` |
| 401 Invalid signature | Wrong secret / `META_APP_SECRET`; regenerate; `config:clear` |
| Meta verify fails | Verify token mismatch; CRM down; using localhost without ngrok |
| MySQL migration “index name too long” | Use current short index migration; drop partial tables + migration row if stuck |
| `channel_connections` already exists | Partial migration — drop channel tables carefully, delete migration record, re-run |

## WhatsApp / Facebook

| Symptom | Fix |
|---|---|
| Test connection fails | Token / Phone Number ID / Page ID wrong |
| No Inbox thread | Worker; adapter; wrong phone number id filter |
| Reply fails | Conversation closed; token expired; Meta 24h window rules |
| Secret leaked in chat | Rotate App Secret + access tokens immediately |

## Mail

| Symptom | Fix |
|---|---|
| No emails arriving | `MAIL_MAILER=log` only writes logs — switch to SMTP/Mailpit |
| Gmail blocked | App Password + correct from address |

## Scheduler

| Symptom | Fix |
|---|---|
| No reminders | Cron missing `schedule:run`; reminder flags disabled in `.env` |
| Heartbeat stale | Cron / app path wrong in crontab |

## Company profile vs settings

| Symptom | Fix |
|---|---|
| `/company/settings` 500 `edit()` missing | Ensure latest code with `CompanySettingsController::edit` restored |

## Getting more diagnostics

```bash
php artisan about
php artisan route:list --path=webhooks
php artisan queue:failed
tail -f storage/logs/laravel.log
```

For Meta local deliveries, open ngrok inspector: http://127.0.0.1:4040

## Related

- [Installation](getting-started/installation.md)
- [Queues](operations/queues.md)
- [Channels overview](channels/overview.md)
