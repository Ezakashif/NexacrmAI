# NexaCRM

NexaCRM is a modern multi-tenant CRM SaaS built on Laravel. It is intended as a ready-to-customize codebase for developers, agencies, and businesses that want to run their own CRM platform.

**Tagline:** A Modern Multi-Tenant CRM for Growing Businesses

This repository is the Codester edition. It is independent of any other CRM product or production environment.

## What it includes

- Public marketing site (home, features, pricing, about, contact, documentation, demo)
- Tenant CRM workspace with AdminLTE
- Super Admin console for companies, plans, platform settings, and impersonation
- Company-scoped multi-tenancy (single database)
- Custom roles and permissions (not Spatie)
- Leads, customers, tasks, lead activities, and kanban boards
- Dashboard, reports, CSV import/export, and global search
- Optional live demo tenant with daily reset
- Channel webhooks: Generic Webhook, Facebook Lead Ads, and WhatsApp Cloud API
- Inbox for WhatsApp conversations
- Plan limits and trial/subscription state (managed in Super Admin; no payment gateway)

Billing is administrative. There is no Stripe, Cashier, or Paddle checkout in this codebase.

## Technology stack

| Layer | Choice |
|---|---|
| Backend | PHP 8.2+, Laravel 12 |
| Auth | Session authentication (Laravel Breeze-style) |
| Tenant UI | AdminLTE 3 |
| Marketing UI | Blade, Tailwind CSS, Alpine.js, Vite |
| Tenancy | Custom `company_id` global scope |
| RBAC | Config-driven permissions synced to the database |
| Queues | Database driver by default |
| PDF | DomPDF |

## Requirements

- PHP 8.2+ with typical Laravel extensions, including `gd`, plus `pdo_mysql` (Composer platform requirement for the supported MySQL install path)
- Composer 2
- Node.js 20+ and npm
- SQLite (local default) or MySQL 8+
- A queue worker and a cron entry for scheduler jobs in production

## Installation (overview)

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan db:seed
php artisan storage:link
php artisan nexacrm:create-super-admin
npm ci && npm run build
php artisan serve
```

`npm install && npm run build` also works. `npm ci` is what CI uses because it follows `package-lock.json`.

`db:seed` loads plans, permissions, and email templates only. It does not create login accounts. It does create a **Default Company** permissions shell — provision a real tenant from Super Admin → Companies. Create the first Super Admin with `php artisan nexacrm:create-super-admin` (interactive) or by setting `SETUP_SUPERADMIN_EMAIL` and `SETUP_SUPERADMIN_PASSWORD` before seeding. Never commit those values.

Alternatively, `composer setup` runs install, env, migrate, **normal** seed, `storage:link`, and the frontend build. It still does not create a login — run `nexacrm:create-super-admin` after it.

In another terminal:

```bash
php artisan queue:work --queue=channels,default
```

A queue worker is not required for first login. It is required for channel webhooks and scheduled reminder jobs.

For production, point the web root at `public/`, set `APP_ENV=production`, configure a real mailer, and run `schedule:run` every minute.

See [docs/getting-started/installation.md](docs/getting-started/installation.md) for the full setup guide.

If you received a Codester ZIP (no Git history), follow [docs/codester-installation.md](docs/codester-installation.md) instead of cloning.

Maintainers can build that ZIP with `composer package`. The archive is written to `dist/` on **your computer** (for example `dist/nexacrm-unreleased-<revision>-codester.zip`). `dist/` is gitignored, so a GitHub clone never contains the ZIP. Git for Windows / Git Bash does not include the `zip` command; the builder then uses PHP `ZipArchive` (enable `extension=zip` in php.ini — XAMPP usually has it) or PowerShell.

Public registration is off until a Super Admin enables it. Email verification is also off on a fresh install so the app stays usable before SMTP is configured.

## Multi-tenancy and RBAC

Each tenant is a `Company`. Tenant models are scoped by `company_id`. Super Admins are platform users (`is_super_admin`) and use `/superadmin`, not the tenant CRM.

Permissions are defined in `config/permissions.php` and synced with `php artisan permissions:sync`. Default company roles are `admin` and `sales`.

## Demo

An optional fictional demo tenant can be seeded separately when `DEMO_SEED_PASSWORD` is set:

```bash
php artisan db:seed --class=DemoDataSeeder
```

Or set `DEMO_SEED=true` before `php artisan db:seed`. Public visitors can use **Try Live Demo** if that tenant exists. Daily reset is off unless `DEMO_RESET_ENABLED=true`. Demo personas use fictional `@demo.nexacrm.test` addresses. Do not seed this on a buyer production install.

Details: [docs/DEMO_ENVIRONMENT.md](docs/DEMO_ENVIRONMENT.md).

## White-label / customization

Platform name, logo, favicon, mail from-address, timezone, and marketing contact details can be changed in Super Admin → Settings. Marketing copy also reads from `config/marketing.php` and `APP_NAME` / `MARKETING_*` environment variables.

Replace the packaged branding files under `public/branding/` (`nexacrm-logo.png`, `nexacrm-logo-light.png`, `nexacrm-mark.svg`) with your own marks when you customize the product.

## Documentation

In-app docs are available at `/docs` after login. The Markdown sources live in [`docs/`](docs/README.md).

## Checks before a pull request

```bash
composer ci
```

That validates Composer metadata and runs PHPUnit. For a fuller local check (Composer + npm lockfile install + Vite build + PHPUnit):

```bash
bash scripts/validate-release.sh
```

Contributors who clone this repository from GitHub can also rely on the repository’s CI workflow. That workflow and maintainer release notes are GitHub-only and are not part of the Codester buyer ZIP.

## License and support

This repository’s software license is **MIT**. See [`LICENSE`](LICENSE). Composer metadata (`ezakashif/nexacrm`) declares the same license.

A Codester purchase is a **separate** marketplace license (Regular or Extended) chosen at checkout. Marketplace terms do not automatically replace MIT, and MIT does not authorize resale of NexaCRM as a stock Codester item. Details, third-party notices, and unresolved marketplace questions: [docs/licensing-audit.md](docs/licensing-audit.md).

Codester seller drafts (Phase 9): [listing copy](docs/codester-listing.md), [media audit](docs/codester-media-audit.md), [submission readiness](docs/codester-submission-review.md). Upload assets (preview, icon, screenshots ZIP, price/profile paste notes): [`codester-upload/`](codester-upload/). Buyer ZIP install: [codester-installation.md](docs/codester-installation.md).

Support terms for buyers can be added once they are defined.
