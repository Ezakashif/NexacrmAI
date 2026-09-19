# Installation

Fresh setup for NexaCRM on a local machine or a typical VPS / shared-hosting account with SSH.

This is a normal Laravel install. There is no web installer. Public registration stays **off** until a Super Admin enables it.

Codester ZIP buyers (no Git clone): [Codester installation](../codester-installation.md).

## Requirements

| Component | Version / notes |
|---|---|
| PHP | 8.2+ with extensions typical for Laravel (`pdo`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`, `gd`). Composer also requires `pdo_mysql` (`ext-pdo_mysql`) because MySQL 8+ is a supported production database. |
| Composer | 2.x |
| Node.js + npm | For Vite frontend assets |
| Database | SQLite (default) or MySQL 8+ |
| Optional | Mailpit (local mail), ngrok (Meta webhooks to localhost) |

## 1. Clone and install PHP dependencies

```bash
git clone <repository-url> nexacrm
cd nexacrm
composer install
```

Or use the project setup script (copies `.env` if missing, generates `APP_KEY`, creates `database/database.sqlite` when needed, migrates, seeds platform defaults, syncs permissions, links storage, and builds assets):

```bash
composer setup
```

`composer setup` does **not** create a login account. Continue with [Create the Super Admin](#4-create-the-super-admin) after it finishes.

## 2. Environment file

```bash
cp .env.example .env
php artisan key:generate
```

Set at least:

```env
APP_NAME="NexaCRM"
APP_URL=http://127.0.0.1:8000
DB_CONNECTION=sqlite
QUEUE_CONNECTION=database
MAIL_MAILER=log
```

For SQLite, ensure the database file exists:

```bash
# Windows PowerShell
New-Item -ItemType File -Force database/database.sqlite

# macOS / Linux
touch database/database.sqlite
```

For MySQL, comment out SQLite and set:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nexacrm
DB_USERNAME=root
DB_PASSWORD=
```

See [Configuration](configuration.md) for the full variable list.

## 3. Database, seed, and storage link

```bash
php artisan migrate
php artisan db:seed
php artisan storage:link
php artisan permissions:sync
```

`db:seed` loads **platform defaults only**:

- permissions and default roles for the platform Default Company shell (not a login-ready tenant)
- email templates
- public plans (Starter, Professional, Enterprise)

It does **not** create Super Admin, tenant, or demo login accounts, and it does **not** insert sample leads, customers, or tasks.

`permissions:sync` upserts permissions from `config/permissions.php` and refreshes default role grants. Safe to re-run after upgrades.

`storage:link` creates `public/storage` → `storage/app/public` so avatars and logos are reachable. Do this on every environment; do not rely on host-specific scripts.

## 4. Create the Super Admin

NexaCRM never ships a known default password. Create the first platform operator yourself.

**Recommended on VPS / shared hosting (interactive):**

```bash
php artisan nexacrm:create-super-admin
```

The command prompts for name, email, and password. Passwords must meet the application rules (minimum 10 characters, mixed case, and a symbol). The first Super Admin is created as a verified, active platform user (`company_id` null) and signs in at `/login`, then uses `/superadmin`. Change that password later from Super Admin → **Account** (`/superadmin/account`).

Non-interactive example (do not commit the password):

```bash
php artisan nexacrm:create-super-admin --name="Super Admin" --email="you@yourdomain.com" --password="choose-a-strong-password"
```

**Optional scripted seed:** set both of these in `.env` *before* `php artisan db:seed`:

```env
SETUP_SUPERADMIN_NAME="Super Admin"
SETUP_SUPERADMIN_EMAIL=you@yourdomain.com
SETUP_SUPERADMIN_PASSWORD=
```

Leave `SETUP_SUPERADMIN_PASSWORD` empty in any file you commit. If only one of email/password is set, seeding fails on purpose. After the account exists, remove `SETUP_SUPERADMIN_PASSWORD` from the environment before `php artisan config:cache`.

If a Super Admin already exists, the command refuses to create another. Add extra operators later from Super Admin → Super Admins.

## 5. First login and first company

1. Open `/login` and sign in with the Super Admin email you created.
2. You are redirected to `/superadmin`.
3. Create a tenant company (with a tenant admin email **and** password) under **Companies**.
   The **Default Company** row is a platform permissions shell, not a usable CRM tenant.
4. Sign out, then sign in as that tenant admin to use `/dashboard`.

Public `/register` stays disabled until Super Admin → Settings → **Registration enabled**.

Confirm you can open:

- Marketing site: `/`
- Super Admin: `/superadmin`
- Tenant CRM: `/login` → `/dashboard` (after a company exists)

## 6. Mail and email verification

The default mailer is `MAIL_MAILER=log` (messages go to `storage/logs`, not inboxes).

Email verification is **off** on a fresh install so registration and tenant logins are not blocked before SMTP exists. Auth mail (verification, password reset) is sent immediately; a queue worker is not required for those messages.

When you are ready to require verification:

1. Configure a real mailer (`smtp`, SES, Postmark, Resend, or Mailpit locally).
2. In Super Admin → Settings, enable **Require email verification**.

If verification is on while the mailer is still `log` or `array`, the verify-email page shows a local preview link so the account can still be confirmed.

Do not enable verification in production until outbound mail is working.

## 7. Frontend assets

Skip this section if you already ran `composer setup`.

```bash
npm ci
npm run build
# npm install && npm run build also works for a local checkout
# or for hot reload during development:
npm run dev
```

GitHub Actions (GitHub clones only) uses `npm ci` so the build matches `package-lock.json`.

## 8. Run the app

**Option A — simple**

```bash
php artisan serve
```

**Option B — full local stack** (server + queue + logs + Vite):

```bash
composer dev
```

### Queues

A queue worker is **not** required to finish first-run (migrate, seed, Super Admin, login). It **is** required for channel webhooks, reminder jobs, and other queued work.

In a separate terminal if not using `composer dev`:

```bash
php artisan queue:work --queue=channels,default
```

Production also needs `php artisan schedule:run` every minute. See [Queues](../operations/queues.md) and [Scheduler](../operations/scheduler.md).

## 9. Optional demo tenant

Do **not** seed demo data on a real customer install.

The fictional Northstar Solutions tenant is opt-in and requires `DEMO_SEED_PASSWORD` in the environment (never commit the value):

```bash
php artisan db:seed --class=DemoDataSeeder
```

Or set `DEMO_SEED=true` in `.env` and run `php artisan db:seed`. Daily reset stays off unless `DEMO_RESET_ENABLED=true`.

Details: [Demo environment](../DEMO_ENVIRONMENT.md).

## 10. Optional: Meta / Channels local testing

1. Set `META_APP_SECRET` in `.env` and run `php artisan config:clear`.
2. Expose localhost with ngrok: `ngrok http 8000`.
3. Connect a channel under **Administration → Channels**.
4. Point Meta webhooks at the ngrok HTTPS URL.

Details: [Channels overview](../channels/overview.md).

## Verification checklist

- [ ] `php artisan about` runs without errors
- [ ] `php artisan test` passes (or at least Feature suite for your area)
- [ ] Super Admin can sign in at `/login` and open `/superadmin`
- [ ] A tenant company can be created from Super Admin
- [ ] Tenant admin can sign in and open `/dashboard`
- [ ] `public/storage` exists (`php artisan storage:link`)
- [ ] Queue worker processes a test job (needed for channels, not for first login)
- [ ] Mail: with `MAIL_MAILER=log`, check `storage/logs` — or use Mailpit on port 1025

## Common install issues

| Symptom | Fix |
|---|---|
| `No application encryption key` | `php artisan key:generate` |
| SQLite “unable to open database” | Create `database/database.sqlite` and check permissions |
| Cannot sign in after seed | Seed does not create users. Run `php artisan nexacrm:create-super-admin` |
| MySQL index name too long (channels migration) | Use current migrations (short custom index names); drop partial tables if a prior run failed mid-way |
| Channels menu missing | `php artisan permissions:sync` |
| CSS/JS missing | `npm run build` |
| Uploaded logos/avatars 404 | `php artisan storage:link` |
| Verification email never arrives | Default mailer is `log`. Configure SMTP, or leave verification off until mail works |

More: [Troubleshooting](../troubleshooting.md).
