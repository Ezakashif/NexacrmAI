# NexaCRM — Demo Environment

Polished fictional tenant for the public live demo, screenshots, and marketing.

## Seed command

`DEMO_SEED_PASSWORD` must be set in the environment. If it is missing, seeding and reset abort.

```bash
php artisan db:seed --class=DemoDataSeeder
```

Or set `DEMO_SEED=true` and run `php artisan db:seed`. Normal platform seeding does **not** include demo data.

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
| Admin | `admin@demo.nexacrm.test` |
| Sales Manager | `manager@demo.nexacrm.test` |
| Sales Representative | `sales@demo.nexacrm.test` |

Passwords exist only as `DEMO_SEED_PASSWORD` (environment secret) and as hashes in the database. They must not appear in source, git, HTML, JavaScript, or public docs.

## What gets seeded

- 1 demo company (Enterprise/Professional plan when available)
- 3 users: Admin, Sales Manager (custom role), Sales Representative
- ~18 leads across pipeline stages
- 8 customers (from won leads)
- ~19 tasks (overdue / upcoming / completed)
- Lead activity timeline entries
- Sample CRM activity-log rows (fictional TEST-NET IPs, not localhost)

Buyer-facing screenshots live in `public/marketing/screenshots/nexacrm-*.png`. Recapture from a running local app:

```bash
DEMO_SEED_PASSWORD='...' node scripts/capture-nexacrm-screenshots.mjs
DEMO_SEED_PASSWORD='...' node scripts/capture-nexacrm-demo-video.mjs
```

The demo video records the marketing home, sign-in, dashboard, leads pipeline, customers, tasks, reports, users, roles, and a return to the dashboard. Add `CAPTURE_SUPERADMIN=1` plus Super Admin credentials only if you want a platform-console segment.

To add narration and a soft ambient music bed (no third-party music license required):

```bash
./scripts/add-demo-video-audio.sh
```

Edit the spoken script in `scripts/demo-video-narration.txt` first if you want different wording. Requires `ffmpeg`, `sox`, and `edge-tts`.

Not included in production `DatabaseSeeder` auto-run.
