# Installation

Local development setup for the multi-tenant CRM.

## Requirements

| Component | Version / notes |
|---|---|
| PHP | 8.2+ with extensions typical for Laravel (`pdo`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`) |
| Composer | 2.x |
| Node.js + npm | For Vite frontend assets |
| Database | SQLite (default) or MySQL 8+ |
| Optional | Mailpit (local mail), ngrok (Meta webhooks to localhost) |

## 1. Clone and install PHP dependencies

```bash
git clone <repository-url> crm
cd crm
composer install
```

Or use the project setup script (installs deps, copies `.env`, generates key, migrates, builds assets):

```bash
composer setup
```

## 2. Environment file

```bash
cp .env.example .env
php artisan key:generate
```

Set at least:

```env
APP_NAME="CRM"
APP_URL=http://127.0.0.1:8000
DB_CONNECTION=sqlite
QUEUE_CONNECTION=database
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
DB_DATABASE=crm
DB_USERNAME=root
DB_PASSWORD=
```

See [Configuration](configuration.md) for the full variable list.

## 3. Migrate and seed

```bash
php artisan migrate
php artisan db:seed
php artisan permissions:sync
```

`permissions:sync` upserts permissions from `config/permissions.php` and refreshes default role grants.

## 4. Frontend assets

```bash
npm install
npm run build
# or for hot reload during development:
npm run dev
```

## 5. Run the app

**Option A — simple**

```bash
php artisan serve
```

**Option B — full local stack** (server + queue + logs + Vite):

```bash
composer dev
```

**Queues (required for channel webhooks)**

In a separate terminal if not using `composer dev`:

```bash
php artisan queue:work --queue=channels,default
```

## 6. First login

After seeding you typically have a default company and users from the database seeders. Create or reset a user as needed:

```bash
php artisan tinker
```

Confirm you can open:

- Marketing site: `/`
- Tenant CRM: `/login` → `/dashboard`
- Super Admin: `/superadmin` (platform Super Admin users only)

## 7. Optional: Meta / Channels local testing

1. Set `META_APP_SECRET` in `.env` and run `php artisan config:clear`.
2. Expose localhost with ngrok: `ngrok http 8000`.
3. Connect a channel under **Administration → Channels**.
4. Point Meta webhooks at the ngrok HTTPS URL.

Details: [Channels overview](../channels/overview.md).

## Verification checklist

- [ ] `php artisan about` runs without errors
- [ ] `php artisan test` passes (or at least Feature suite for your area)
- [ ] Dashboard loads for a tenant admin
- [ ] Queue worker processes a test job
- [ ] Mail: with `MAIL_MAILER=log`, check `storage/logs` — or use Mailpit on port 1025

## Common install issues

| Symptom | Fix |
|---|---|
| `No application encryption key` | `php artisan key:generate` |
| SQLite “unable to open database” | Create `database/database.sqlite` and check permissions |
| MySQL index name too long (channels migration) | Use current migrations (short custom index names); drop partial tables if a prior run failed mid-way |
| Channels menu missing | `php artisan permissions:sync` |
| CSS/JS missing | `npm run build` |

More: [Troubleshooting](../troubleshooting.md).
