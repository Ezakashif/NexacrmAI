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

- PHP 8.2+ with typical Laravel extensions, including `gd` and `pdo_mysql`
- Composer 2
- Node.js 20+ and npm
- SQLite (local default) or MySQL 8+
- A queue worker and a cron entry for scheduler jobs in production

## Installation (overview)

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan permissions:sync
php artisan storage:link
npm install && npm run build
php artisan serve
```

In another terminal:

```bash
php artisan queue:work --queue=channels,default
```

For production, point the web root at `public/`, set `APP_ENV=production`, configure a real mailer, and run `schedule:run` every minute.

See [docs/getting-started/installation.md](docs/getting-started/installation.md) for the full setup guide.

Default seeded logins (change these after install):

- Super Admin: `superadmin@example.com`
- Tenant admin: `admin@example.com`
- Sales: `sales@example.com`

The seed password is currently `password`. Treat that as a development default, not a production credential.

## Multi-tenancy and RBAC

Each tenant is a `Company`. Tenant models are scoped by `company_id`. Super Admins are platform users (`is_super_admin`) and use `/superadmin`, not the tenant CRM.

Permissions are defined in `config/permissions.php` and synced with `php artisan permissions:sync`. Default company roles are `admin` and `sales`.

## Demo

An optional fictional demo tenant can be seeded separately (`DemoDataSeeder`) when `DEMO_SEED_PASSWORD` is set. Public visitors can use **Try Live Demo** if that tenant exists. Daily reset is off unless `DEMO_RESET_ENABLED=true`.

Details: [docs/DEMO_ENVIRONMENT.md](docs/DEMO_ENVIRONMENT.md).

## White-label / customization

Platform name, logo, favicon, mail from-address, timezone, and marketing contact details can be changed in Super Admin → Settings. Marketing copy also reads from `config/marketing.php` and `APP_NAME` / `MARKETING_*` environment variables.

Replace the packaged branding files under `public/branding/` with your own marks when you customize the product.

## Documentation

In-app docs are available at `/docs` after login. The Markdown sources live in [`docs/`](docs/README.md).

## License and support

The repository currently includes an MIT `LICENSE` file. For a Codester commercial source-code release, that license should be reviewed and replaced with the marketplace terms you intend to ship. Do not treat the current MIT file as the final Codester license until that review is complete.

Support terms for buyers can be added here once they are defined.
