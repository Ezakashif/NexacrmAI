# Configuration

Environment and config reference for the CRM.

Primary source: `.env` / `.env.example`. Most values map into Laravel `config/*.php` files.

After changing `.env`:

```bash
php artisan config:clear
# production:
php artisan config:cache
```

---

## Application

| Variable | Default (example) | Purpose |
|---|---|---|
| `APP_NAME` | Laravel | Display name / mail from name |
| `APP_ENV` | local | Environment |
| `APP_KEY` | _(generated)_ | Encryption (required) |
| `APP_DEBUG` | true | Detailed errors (disable in production) |
| `APP_URL` | http://localhost | Must match the URL you open (include port) |
| `APP_LOCALE` | en | Locale |
| `LOG_CHANNEL` | stack | Logging |
| `LOG_LEVEL` | debug | Log verbosity |
| `BCRYPT_ROUNDS` | 12 | Password hashing cost |

---

## Database & session / cache

| Variable | Default | Purpose |
|---|---|---|
| `DB_CONNECTION` | sqlite | `sqlite` or `mysql` |
| `DB_HOST` / `DB_PORT` / `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` | _(commented)_ | MySQL settings |
| `SESSION_DRIVER` | database | Session store |
| `SESSION_LIFETIME` | 120 | Minutes |
| `SESSION_SECURE_COOKIE` | false | Set `true` behind HTTPS in production |
| `CACHE_STORE` | database | Cache backend |

---

## Queue

| Variable | Default | Purpose |
|---|---|---|
| `QUEUE_CONNECTION` | database | Use `database` in local/prod; tests force `sync` |
| `CHANNELS_WEBHOOK_QUEUE` | channels | Queue name for channel webhook jobs |
| `CHANNELS_WEBHOOK_RATE_LIMIT` | 60 | Per-minute throttle for `/webhooks/channels/{uuid}` |

See [Queues](../operations/queues.md).

---

## Mail

| Variable | Default | Purpose |
|---|---|---|
| `MAIL_MAILER` | log | `log` writes to storage logs (not real inboxes) |
| `MAIL_HOST` / `MAIL_PORT` | 127.0.0.1 / 2525 | SMTP |
| `MAIL_USERNAME` / `MAIL_PASSWORD` | null | SMTP auth |
| `MAIL_FROM_ADDRESS` / `MAIL_FROM_NAME` | hello@example.com / `${APP_NAME}` | From header |

**Local inbox tip:** Mailpit + `MAIL_MAILER=smtp`, `MAIL_HOST=127.0.0.1`, `MAIL_PORT=1025`.

Auth emails (verification / password reset) send immediately (no queue worker required).

---

## Website lead webhook

Server-to-server form ingestion (not the Channels “Website Forms” adapter).

| Variable | Purpose |
|---|---|
| `WEBSITE_LEAD_WEBHOOK_SECRET` | Shared secret header validation — **never** expose in browser JS |
| `WEBSITE_LEAD_CREATED_BY_EMAIL` | Fallback owner email for created leads |
| `WEBSITE_LEAD_RATE_LIMIT` | Requests per minute (default 10) |

Endpoint: `POST /webhooks/leads/website`  
Docs: [Website Forms](../channels/website-forms.md).

---

## Channels / Meta

| Variable | Default | Purpose |
|---|---|---|
| `META_APP_SECRET` | _(empty)_ | Validates Meta `X-Hub-Signature-256` for Facebook Lead Ads & WhatsApp |
| `META_GRAPH_VERSION` | v21.0 | Graph API version |
| `CHANNELS_WEBHOOK_QUEUE` | channels | Job queue |

Per-tenant tokens (page token, WhatsApp token, verify token) are stored **encrypted on each `channel_connections` row**, not in `.env`.

---

## Lead follow-up reminders

| Variable | Default | Purpose |
|---|---|---|
| `LEAD_FOLLOW_UP_REMINDERS_ENABLED` | true | Master switch |
| `LEAD_FOLLOW_UP_REMINDER_TIME` | 08:00 | Daily schedule time |
| `LEAD_FOLLOW_UP_DEFAULT_TIME` | 09:00 | Default follow-up time of day |
| `LEAD_FOLLOW_UP_REMINDER_1D_ENABLED` | true | Day-before tier |
| `LEAD_FOLLOW_UP_REMINDER_2H_ENABLED` | true | Hours-before tier |
| `LEAD_FOLLOW_UP_REMINDER_DUE_ENABLED` | true | Due tier |
| `LEAD_FOLLOW_UP_REMINDER_OVERDUE_ENABLED` | true | Overdue tier |
| `LEAD_FOLLOW_UP_REMINDER_OVERDUE_REPEAT_DAYS` | 1 | Overdue repeat interval |

---

## Task reminders

| Variable | Default | Purpose |
|---|---|---|
| `TASK_REMINDERS_ENABLED` | true | Master switch |
| `TASK_REMINDER_TIME` | 08:15 | Daily schedule time |
| `TASK_REMINDER_DUE_ENABLED` | true | Due tier |
| `TASK_REMINDER_OVERDUE_ENABLED` | true | Overdue tier |
| `TASK_REMINDER_OVERDUE_REPEAT_DAYS` | 1 | Overdue repeat |

See [Scheduler](../operations/scheduler.md).

---

## Tenancy

| Variable | Purpose |
|---|---|
| `TENANCY_FAIL_CLOSED_WITHOUT_CONTEXT` | When set, forces fail-closed (or open) company scoping without context. If unset, production fails closed and local/dev does not. |

See [Multi-tenancy](../architecture/multi-tenancy.md).

---

## Related config files

| File | Role |
|---|---|
| `config/permissions.php` | RBAC module/action registry |
| `config/channels.php` | Channel providers + Meta + webhook settings |
| `config/tenancy.php` | Tenant fail-closed behavior |
| `config/website_leads.php` | Website webhook settings |
| `config/lead_reminders.php` / `config/task_reminders.php` | Reminder schedules |
| `config/adminlte.php` | Tenant sidebar / branding |
| `config/queue.php` | Queue connections |
