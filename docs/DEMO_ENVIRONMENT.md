# Algos CRM — Demo Environment

Polished fictional tenant for the public live demo, screenshots, and marketing.

## Seed command

`DEMO_SEED_PASSWORD` must be set in the environment. If it is missing, seeding and reset abort.

```bash
php artisan db:seed --class=DemoDataSeeder
```

Safe to re-run. Upserts the Northstar Solutions tenant only; does not wipe other companies.

If a demo persona email already belongs to a different company, seeding **aborts** and does not move or overwrite that user.

## Reset command

```bash
php artisan demo:reset
```

Resets **only** the verified Northstar tenant (`northstar-solutions`), then re-seeds it. Refuses to run if:

- the company cannot be found
- the slug is not exactly `northstar-solutions`
- the resolved company is the default company

Do **not** run `demo:reset` against production unless it is explicitly approved. Use local or staging.

The command is registered on the existing `schedule:run` cron. It uses `withoutOverlapping()` and runs only when `DEMO_RESET_ENABLED=true`. Leave that flag false until production reset is explicitly approved.

## Identity

| Field | Value |
|-------|--------|
| Company | Northstar Solutions |
| Slug | `northstar-solutions` |

Passwords exist only as `DEMO_SEED_PASSWORD` (environment secret) and as hashes in the database. They must not appear in source, git, HTML, JavaScript, or public docs.

## What gets seeded

- 1 demo company (Enterprise/Professional plan when available)
- 3 users: Admin, Sales Manager (custom role), Sales Representative
- ~18 leads across pipeline stages
- 8 customers (from won leads)
- ~19 tasks (overdue / upcoming / completed)
- Lead activity timeline entries

Not included in production `DatabaseSeeder` auto-run.
