# NexaCRM AI

**NexaCRM AI** is the AI-assisted edition of NexaCRM — a modern multi-tenant CRM SaaS built on Laravel. This repository is a separate product fork of the Codester NexaCRM codebase, with branding and an AI lead-assist scaffold ready for provider keys.

**Tagline:** AI-Assisted Multi-Tenant CRM for Growing Businesses

**Wordmark:** `nexacrm.ai`

This repo is independent of the non-AI NexaCRM Codester package and of any other CRM product or production environment.

## What it includes

Everything in the NexaCRM CRM foundation, plus:

- **AI lead assist scaffold** on lead detail pages (summary, next steps, talk track)
- Provider-ready clients for **OpenAI** and **Anthropic** (disabled by default)
- `AI_ENABLED` feature flag and `ai_assist.leads` permission
- Marketing/docs branding for **NexaCRM AI**

Core CRM modules:

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

## AI setup (optional)

AI is **off** until you enable it. Without keys, the lead page shows a scaffolding card and the Null client is bound.

```env
AI_ENABLED=true
AI_DEFAULT_PROVIDER=openai   # or anthropic
AI_OPENAI_API_KEY=sk-...
# or
AI_ANTHROPIC_API_KEY=sk-ant-...
```

Then open a lead and use **Suggest next steps**. Users need the `ai_assist.leads` permission (included for Admin and Sales in the default RBAC seed).

See [docs/ai.md](docs/ai.md) for architecture notes.

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
| AI (optional) | OpenAI / Anthropic HTTP clients |

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
touch database/database.sqlite   # if using sqlite
php artisan migrate --seed
npm install && npm run build
php artisan serve
```

Create the first Super Admin:

```bash
php artisan nexacrm:create-super-admin
```

## Demo tenant

Seed and use the live demo when `DEMO_SEED=true` (see `.env.example` and `docs/demo-environment.md`).

## License

MIT for application source. Marketplace / Codester terms apply when distributed as a commercial listing. See `LICENSE` and documentation under `docs/`.

## Relationship to NexaCRM (non-AI)

| | NexaCRM (Codester) | NexaCRM AI (this repo) |
|---|---|---|
| Brand | NexaCRM | NexaCRM AI / `nexacrm.ai` |
| AI features | Not included | Scaffold + optional providers |
| Repository | Separate Codester package | [Ezakashif/NexacrmAI](https://github.com/Ezakashif/NexacrmAI) |
