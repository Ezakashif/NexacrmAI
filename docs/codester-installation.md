# NexaCRM Codester installation

How to install NexaCRM from the Codester source ZIP. This is a normal Laravel application. There is no web installer.

This package is independent of any other CRM product. Do not point it at another product’s production database.

Git clone instructions for maintainers: [getting-started/installation.md](getting-started/installation.md).

## Requirements

| Component | Version / notes |
|---|---|
| PHP | 8.2+ with `pdo`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`, `gd`. Composer also requires the `pdo_mysql` extension (`ext-pdo_mysql` in `composer.json`) even when you use SQLite locally, because MySQL 8+ is a supported production database. |
| Composer | 2.x |
| Node.js | 20+ (see `.node-version`) and npm |
| Database | SQLite (simplest local default) or MySQL 8+ (recommended for production) |
| Web server | Document root must be the `public/` directory in production |

A queue worker and a scheduler cron entry are **not** required to finish first-run or sign in. They are required in production for channel webhooks and reminder emails.

## 1. Extract the package

```bash
unzip nexacrm-unreleased-<revision>-codester.zip
cd nexacrm-unreleased-<revision>-codester
```

You should see `artisan`, `composer.json`, `composer.lock`, `.env.example`, `LICENSE`, and `docs/`.

Do not copy a `.env` file from another project.

## 2. PHP dependencies

The ZIP does **not** include `vendor/`. Buyers run Composer:

```bash
composer install
```

Use the committed `composer.lock` (do not run `composer update` for first install).

## 3. Environment file

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
CACHE_STORE=database
SESSION_DRIVER=database
MAIL_MAILER=log
DEMO_SEED=false
```

`APP_URL` must match the URL you open in the browser (include the port).

### SQLite

```bash
touch database/database.sqlite
```

Leave `DB_CONNECTION=sqlite`. You do not need `DB_HOST` / `DB_USERNAME`.

### MySQL

Create an **empty** database that is not used by any other application, then:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nexacrm
DB_USERNAME=root
DB_PASSWORD=
```

See [Configuration](getting-started/configuration.md) for the full variable list.

## 4. Database, seed, storage link

```bash
php artisan migrate
php artisan db:seed
php artisan storage:link
```

`db:seed` loads **platform defaults only**: permissions, email templates, and plans. It does **not** create demo users, demo companies, demo leads, demo customers, or login passwords.

It does create a **Default Company** row used as a permissions shell. That row is not a usable CRM tenant. Provision a real company after you create the Super Admin.

`storage:link` makes `public/storage` point at `storage/app/public` (avatars and logos).

Optional after upgrades: `php artisan permissions:sync`.

## 5. Create the Super Admin

NexaCRM does not ship a default password.

```bash
php artisan nexacrm:create-super-admin
```

The command prompts for name, email, and password (minimum 10 characters, mixed case, and a symbol).

Non-interactive example (do not commit the password):

```bash
php artisan nexacrm:create-super-admin --name="Super Admin" --email="you@yourdomain.com" --password="choose-a-strong-password"
```

Sign in at `/login`, then open `/superadmin`. Public registration stays off until you enable it in Super Admin → Settings.

## 6. Frontend assets

The ZIP does **not** include `node_modules/` or compiled Vite output (`public/build`).

```bash
npm ci
npm run build
```

`npm install && npm run build` also works. `npm ci` follows `package-lock.json`.

Tenant AdminLTE CSS/JS under `public/vendor/` is already in the package and does not come from npm.

## 7. Start the app (local)

```bash
php artisan serve
```

Open `http://127.0.0.1:8000/` (marketing) and `http://127.0.0.1:8000/login`.

## 8. First company (tenant)

1. Sign in as Super Admin.
2. Go to Super Admin → Companies.
3. Create a company. If you set a tenant admin email, you **must** also set that admin’s password (the app will not invent one).
4. Sign out, then sign in as the tenant admin to use the CRM.

## 9. Optional demo tenant

Only if you want the fictional Northstar Solutions demo:

```bash
# set DEMO_SEED_PASSWORD in .env first — never commit it
php artisan db:seed --class=DemoDataSeeder
```

Or set `DEMO_SEED=true` before `php artisan db:seed`. Public **Try Live Demo** works only after that tenant exists.

Do **not** enable demo seed on a buyer production install.

Persona emails are fictional `@demo.nexacrm.test` addresses. The password is whatever you set in `DEMO_SEED_PASSWORD`. It is not stored in this package.

## 10. Production notes

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://YOUR_DOMAIN`
- `SESSION_SECURE_COOKIE=true` behind HTTPS
- Real `MAIL_MAILER` (SMTP / SES / Resend / Postmark) before turning on email verification
- Document root: `public/`
- Writable: `storage/` and `bootstrap/cache/`
- Queue worker: `php artisan queue:work --queue=channels,default`
- Scheduler every minute: `php artisan schedule:run`
- Leave `DEMO_SEED=false` and empty `SETUP_SUPERADMIN_PASSWORD` after first-run
- Do not run `php artisan config:cache` while a Super Admin password is still in `.env`

Web server user must own or write `storage/` and `bootstrap/cache/`. Uploaded logos live on the `public` disk (`storage/app/public`).

## 11. Troubleshooting

| Symptom | What to try |
|---|---|
| 500 after extract | `composer install`, then `php artisan key:generate`. Confirm `storage/` and `bootstrap/cache` are writable. |
| Blank styles on marketing pages | Run `npm ci && npm run build`. Confirm `public/build/manifest.json` exists. |
| SQLSTATE / connection refused | `.env` still points at the wrong host. `php artisan config:clear` then `php artisan db:show`. |
| `No application encryption key` | `php artisan key:generate` |
| Login loops to `/login` | `APP_URL` does not match the browser URL, or cookies blocked. |
| `DemoDataSeeder` fails | `DEMO_SEED_PASSWORD` is empty. |
| Super Admin command refuses | A Super Admin already exists. Use Super Admin → Super Admins to add more. |
| Mixed-content / wrong URLs | Set `APP_URL` to the public HTTPS origin. |
| Permission denied on `storage` | `chown`/`chmod` the `storage` and `bootstrap/cache` trees for the web user. |

More: [Troubleshooting](troubleshooting.md).

## 12. Tests (optional)

```bash
php artisan test
```

PHPUnit uses in-memory SQLite (`phpunit.xml`). It does not use your application database.

## License

NexaCRM application source is MIT (`LICENSE`). Third-party notices: [THIRD-PARTY-NOTICES.md](../THIRD-PARTY-NOTICES.md) and [licensing audit](licensing-audit.md). A Codester purchase is also subject to the Regular or Extended license chosen at checkout. This guide does not replace those terms.
