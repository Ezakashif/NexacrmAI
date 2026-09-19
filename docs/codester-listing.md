# NexaCRM — Codester listing copy (draft)

Seller-facing draft for the Codester item page. Paste into the upload form; do not treat this file as a custom EULA.

**Draft date:** 17 September 2026  
**Source revision:** `de7387f` (`main` at this review)  
**Package filename:** `nexacrm-unreleased-de7387f-codester.zip`

Do not use “Algos CRM” anywhere on the Codester listing.

---

## Product title

NexaCRM — Modern Multi-Tenant CRM SaaS

---

## Short description

Codester’s upload guide allows **130 characters**. Count includes spaces.

**Recommended (122 characters):**

NexaCRM is a self-hostable Laravel multi-tenant CRM for developers and agencies: leads, customers, tasks, and Super Admin.

**Keyword-oriented alternative (115 characters):**

NexaCRM is a Laravel PHP CRM: multi-tenant companies, pipelines, customers, tasks, and a Super Admin you self-host.

---

## Category and attributes

Use the live Codester category tree at upload time. As of 17 September 2026 the CRM PHP scripts browse path is:

**Scripts & Code → PHP Scripts → CRM**  
(`https://www.codester.com/categories/322/crm-software-php-scripts`)

**Suggested file types included** (select all that apply in the form): PHP, CSS, HTML, JavaScript, SQL, JSON.

Framework attribute: **Laravel**.

---

## Suggested tags

crm, laravel, php, saas, multi-tenant, kanban, lead-management, customer-management, task-management, adminlte

Do not tag AI, invoicing, Stripe, or SSO. Those are not in this package.

---

## Price and development time

Codester lets the seller set the item price. The public upload guide’s rule of thumb is development hours × 1.2; that is guidance, not a required formula.

Comparable items in the same CRM PHP category (observed 17 September 2026) listed from about **$22 to $155**.

**Recommended Codester item prices** (seller may adjust; see `codester-upload/README.md`):

| License | Recommended |
|---|---|
| Regular | **$49** |
| Extended | **$129** |

Enter **your real** development hours only if the form asks — do not invent hours. These marketplace prices are not the in-app Starter/Professional/Enterprise plan amounts.

Offer **Regular** and **Extended** licenses using Codester’s checkout options. See [License](#license) below.

---

## Full description (paste)

NexaCRM is a self-hostable, multi-tenant CRM written in Laravel. You receive the application source so you can run it on your own server, white-label it, and customize it for a business or for clients.

It is aimed at developers, agencies, and operators who want a CRM they control: company-scoped data, roles and permissions, a Super Admin console, and a tenant workspace for day-to-day sales work. It is not a hosted subscription from the author. There is no bundled payment gateway (no Stripe, Cashier, or Paddle checkout). Plan limits and trial/subscription state are administered in Super Admin.

### What buyers receive

The download is a `.zip` of the NexaCRM source tree plus English documentation.

- Application source (`app/`, `routes/`, `resources/`, `database/` migrations and seeders)
- Public assets including the AdminLTE tenant UI stack under `public/vendor/`, NexaCRM branding, marketing screenshots, and the product demo video
- `.env.example` (copy this to `.env`; a filled `.env` is not included)
- `composer.json` / `composer.lock` and `package.json` / `package-lock.json`
- `LICENSE` (MIT), `THIRD-PARTY-NOTICES.md`, and docs under `docs/`
- Buyer install guide: `docs/codester-installation.md`

**Not included (by design):**

- `vendor/` — run `composer install` from the lockfile
- `node_modules/` and compiled Vite output (`public/build`) — run `npm ci` and `npm run build`
- `.env`, `.git/`, local SQLite databases, and seller CI (`.github/`)

### Who it is for

- Developers who want a Laravel CRM to extend
- Agencies delivering a CRM workspace to a client (use the license that matches how many projects you will ship)
- Businesses that will self-host and operate their own tenants

### What it does

Each tenant is a **company**. Tenant records are scoped by `company_id`. Super Admins are platform operators (`company_id` null) and use `/superadmin`, not the tenant CRM.

**Tenant CRM**

- Dashboard with follow-ups, tasks, pipeline totals, and lead-source charts
- Leads: list and Kanban board, assignment, activities, convert to customer, CSV import/export
- Customers: accounts after a win or a direct add, CSV import/export
- Tasks: list and Kanban board, assignees, due dates, CSV export
- Reports with filters and CSV export
- Inbox for WhatsApp Cloud conversations (when a company connects that channel)
- In-app notifications and a notification preference screen on the user profile
- Global search
- Activity log
- Company profile and company settings (logo, regional defaults, business hours)
- Users, invitations, and per-company roles with permission checkboxes
- Website Lead Demo page for the website-lead webhook (permission-gated)

**Lead channels (per company)**

- Generic signed webhook
- Facebook Lead Ads
- WhatsApp Cloud API (inbound + inbox reply)

A separate website-lead webhook (`POST /webhooks/leads/website`) can create leads from your own backend. Do not put that secret in browser JavaScript.

**Super Admin**

- Platform dashboard and cross-tenant search
- Companies: create, status, restore, CSV/PDF, impersonation
- Subscription plans and limits (`max_users` / `max_leads` / `max_customers`; null means unlimited)
- Additional Super Admin operators
- Platform settings (name, logo, favicon, registration, email verification, maintenance, announcement)
- Email templates, contact inquiries, demo-visit inbox, notifications
- Account page to change your own Super Admin password

**Marketing site**

Public pages for home, features, pricing, about, contact, documentation, and an optional live-demo entry. Pricing numbers on the marketing site are **in-app plan displays**, not the Codester item price. Super Admins configure real plan names and limits.

**What this package does not include**

- Payment collection (Stripe/Paddle/etc.)
- A web installer
- SSO (the marketing comparison table marks SSO as roadmap; do not advertise it as shipped)
- Invoice/quote modules
- Instagram, Messenger, or a public REST API (documented as planned, not shipped)

### Installation (summary)

This is a normal Laravel app. Full steps: `docs/codester-installation.md`.

1. Extract the ZIP.
2. `composer install` (uses `composer.lock`).
3. `cp .env.example .env` then `php artisan key:generate`.
4. SQLite (`touch database/database.sqlite`) or a **new** MySQL 8+ database that is not used by another application.
5. `php artisan migrate`, `php artisan db:seed`, `php artisan storage:link`.
6. `php artisan nexacrm:create-super-admin` (no default password is shipped).
7. `npm ci && npm run build`.
8. Point the web root at `public/` in production, or `php artisan serve` locally.
9. In Super Admin → Companies, create a tenant (if you enter an admin email you must also set that admin’s password).

`db:seed` loads permissions, email templates, and plans only. It does not create demo logins. It does create a Default Company **permissions shell** that is not a usable CRM tenant.

A queue worker and scheduler cron are **not** required to sign in. They **are** required in production for channel webhooks and reminder emails.

### Customization

You can change platform name, logo, favicon, mail from-address, timezone, and marketing contact details in Super Admin → Settings, and via `config/marketing.php` / `MARKETING_*` environment variables. Replace files under `public/branding/` when you white-label the product. Roles and permissions are config-driven (`config/permissions.php`) and synced with `php artisan permissions:sync`.

---

## Feature list (marketplace)

- Laravel 12 multi-tenant CRM (company-scoped data)
- Super Admin console for companies, plans, branding, and impersonation
- Tenant dashboard, reports, and activity log
- Lead management with list + Kanban pipeline and convert-to-customer
- Customer management
- Task management with list + Kanban
- CSV import/export for leads, customers, and users (tasks export)
- Custom roles and permissions (not Spatie)
- In-app notifications and user profile (password, photo, sessions, preferences)
- Global search
- Optional lead channels: Generic Webhook, Facebook Lead Ads, WhatsApp Cloud API + Inbox
- Website lead webhook + permissioned demo page
- Plan limits and subscription status managed in Super Admin (no payment gateway)
- Public marketing site (Blade / Tailwind / Alpine / Vite)
- Tenant UI on AdminLTE 3
- Optional fictional demo tenant (Northstar Solutions) when you set `DEMO_SEED_PASSWORD`
- English documentation, including a Codester ZIP install guide
- PHPUnit test suite (`php artisan test`) for developers who want to run checks after install

---

## Technical stack

Taken from `composer.json`, `composer.lock`, `package.json`, and `.node-version` at revision `de7387f`.

| Layer | Actual value |
|---|---|
| PHP | `^8.2` (`composer.json`) |
| Laravel | `^12.0` (lockfile: `laravel/framework` **v12.62.0**) |
| Auth | Session authentication |
| Tenant UI | AdminLTE **v3.2.0** via `jeroennoten/laravel-adminlte` **v3.16.0** (Bootstrap 4) |
| Marketing UI | Blade, Tailwind CSS 3, Alpine.js 3, Vite 7 |
| PDF | `barryvdh/laravel-dompdf` ^3.1 (Dompdf is LGPL — see `THIRD-PARTY-NOTICES.md`) |
| Optional mail client | `resend/resend-php` ^1.7 |
| Composer | 2.x |
| Node | `>=20` (`package.json` engines); `.node-version` is **22** |
| Database | SQLite (local default) or MySQL 8+ |
| Queues / cache / sessions | Database drivers in `.env.example` |

Do not list Spatie, Livewire, or Stripe. They are not dependencies.

---

## Server requirements

**Required to install and sign in**

- PHP 8.2+ with `pdo`, `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`, `gd` (`pdo_mysql` is required by Composer because MySQL 8+ is supported)
- Composer 2.x
- Node.js 20+ and npm
- SQLite **or** MySQL 8+
- Writable `storage/` and `bootstrap/cache/`
- Web server document root = `public/` (production)

**Not required for first login; required in production for channels and reminders**

- Queue worker: `php artisan queue:work --queue=channels,default`
- Scheduler every minute: `php artisan schedule:run`

**Optional**

- Real SMTP / SES / Resend / Postmark before turning on email verification
- Redis (`.env.example` includes Redis keys; the default install uses the database store)
- S3-compatible disk if you set `FILESYSTEM_DISK=s3` (credentials stay empty in `.env.example`)
- Demo tenant seed (`DEMO_SEED` / `DemoDataSeeder`) — not for production buyer installs

---

## Installation

Concise steps are in the full description above.

Canonical buyer document inside the ZIP:

**`docs/codester-installation.md`**

Maintainer clone path (not required for ZIP buyers): `docs/getting-started/installation.md`.

---

## What's included

| In the ZIP | Not in the ZIP |
|---|---|
| Laravel application source | `vendor/` (run `composer install`) |
| `public/vendor` AdminLTE stack | `node_modules/` (run `npm ci`) |
| `.env.example` | `.env` |
| Docs, MIT `LICENSE`, third-party notices | `.git/` and `.github/` |
| Tests + `phpunit.xml` | Local `database.sqlite` |
| Branding, screenshots, demo MP4 | Compiled `public/build` (run `npm run build`) |

---

## Demo

This repository does **not** ship a public production demo URL or any demo password.

**If you host a live demo for Codester:**

- Use a dedicated demo instance of **this** NexaCRM package (not another product).
- Codester’s upload guide: the demo URL must **not** contain links to buy the item elsewhere.
- PHP scripts are expected to have a demo; Codester notes demos may run in an iframe — test that.
- Optional YouTube URL (additional). You can upload `public/marketing/videos/nexacrm-product-demo.mp4` to YouTube yourself.

**Demo-only personas** (emails only — the password is whatever you set as `DEMO_SEED_PASSWORD` on **your** demo host; never publish that password on Codester):

| Role | Email |
|---|---|
| Admin | `admin@demo.nexacrm.test` |
| Sales Manager | `manager@demo.nexacrm.test` |
| Sales Representative | `sales@demo.nexacrm.test` |

Seed with `php artisan db:seed --class=DemoDataSeeder` after setting `DEMO_SEED_PASSWORD` in the demo server’s environment. Daily reset stays off unless you set `DEMO_RESET_ENABLED=true`.

On the listing, say clearly that these accounts are **demo-only** and that the ZIP does not contain a default password.

---

## License

Two layers apply. Do not tell buyers that MIT replaces Codester’s checkout license.

### 1. Software license in the ZIP

NexaCRM application source is **MIT** (`LICENSE`, copyright Uneza 2026). Composer package `ezakashif/nexacrm` also declares MIT.

MIT does **not** mean the buyer may resell NexaCRM as a stock Codester (or other marketplace) item. Third-party components keep their own licenses (Laravel MIT, AdminLTE MIT, Dompdf **LGPL-2.1**, Font Awesome Free, and others listed in `THIRD-PARTY-NOTICES.md`).

### 2. Codester marketplace license (chosen at purchase)

Checked on [codester.com/info/licenses](https://www.codester.com/info/licenses) on **17 September 2026**:

- Items are **licensed, not sold**. The author keeps ownership.
- **Regular License:** one project; personal and commercial use, including work on behalf of a client. The item cannot be offered for resale on its own or as part of a project. Distribution of source files is not permitted.
- **Extended License:** unlimited / multiple projects; personal and commercial use. The item cannot be offered for resale **as-is**. Source files may be distributed or sublicensed **as part of a larger project**.

Use the Regular license for one end product. Use Extended if you will reuse the item across multiple client projects or ship source as part of a larger product. Neither license allows reselling this CRM as-is on a marketplace.

No custom EULA was added to this repository.

---

## Preview, icon, and screenshot ZIP (upload form, not the product ZIP)

Codester’s current upload guide ([codester.com/info/upload](https://www.codester.com/info/upload), 17 September 2026):

| Asset | Requirement | Ready file |
|---|---|---|
| Preview image | **800×400** PNG or JPG | `codester-upload/nexacrm-codester-preview-800x400.png` |
| Icon | **200×200** PNG or JPG (not a raw screenshot) | `codester-upload/nexacrm-codester-icon-200x200.png` |
| Screenshots ZIP | JPG or PNG, **at least 3 and at most 9** | `codester-upload/nexacrm-codester-screenshots.zip` (8 unique) |
| Main file | `.zip` with documentation inside | Phase 8 `composer package` output |
| Demo URL | Live demo (PHP scripts); no “buy elsewhere” CTA | Seller hosts (see `codester-upload/README.md`) |
| Video URL | YouTube (additional) | Optional: upload `public/marketing/videos/nexacrm-product-demo.mp4` |

Regenerate preview/icon/screenshots ZIP with `python3 scripts/build-codester-upload-assets.py`.

An older support article still mentions a 1600×800 preview. Prefer the live upload guide (800×400) unless the upload form itself asks for a different size that day.

---

## Seller upload checklist

Copy this into your Codester author notes:

1. [ ] Main file = `composer package` ZIP from current `main`
2. [ ] Documentation inside the ZIP (`docs/codester-installation.md` present)
3. [x] 800×400 preview — `codester-upload/nexacrm-codester-preview-800x400.png`
4. [x] 200×200 icon — `codester-upload/nexacrm-codester-icon-200x200.png`
5. [x] Screenshots ZIP — `codester-upload/nexacrm-codester-screenshots.zip` (8 unique)
6. [ ] Title, short description (≤130 characters), full description pasted from this file
7. [ ] Category PHP Scripts / CRM; tags from this file
8. [ ] Regular **$49** + Extended **$129** (or your adjusted prices)
9. [ ] Live demo URL on **your** NexaCRM host, iframe-tested, no off-site purchase CTA
10. [ ] Demo emails listed as demo-only; **no password** on the listing
11. [ ] Optional YouTube URL of `nexacrm-product-demo.mp4`
12. [ ] Author profile complete (paste from `codester-upload/README.md`)
13. [ ] License text matches the live Codester licenses page
14. [ ] No “Algos CRM”, Railway, or Product Hunt on the listing
