# Release readiness

How NexaCRM is validated for a Codester-style source release. This is **not** Codester ZIP packaging, listing copy, or production deployment.

This repository is independent of any other CRM product. CI never talks to Railway, Algos CRM, or a production database.

---

## CI

| Item | Value |
|---|---|
| Workflow | [`.github/workflows/ci.yml`](../.github/workflows/ci.yml) |
| Triggers | Pull requests, and pushes to `main` |
| Permissions | `contents: read` only. No deploy job. No production secrets. |
| PHP | 8.2 (matches `composer.json` `^8.2`) |
| Node | 20 (matches `package.json` `engines.node`) |
| Database | Isolated SQLite file for the install path; PHPUnit uses in-memory SQLite (`phpunit.xml`) |
| Composer | `composer validate --no-check-publish` then `composer install` from `composer.lock` |
| Frontend | `npm ci` then `npm run build` (Vite) |
| Tests | `php artisan test` (full PHPUnit suite) |

The workflow also:

1. Copies `.env.example`, generates `APP_KEY`, creates `database/database.sqlite`
2. Runs `php artisan migrate --force`
3. Runs **normal** `php artisan db:seed --force` (`DEMO_SEED=false`)
4. Creates a throwaway Super Admin with `php artisan nexacrm:create-super-admin` (CI-only credentials, not production)
5. Asserts the demo tenant was **not** created (`scripts/ci-assert-normal-seed.php`)
6. Runs `storage:link`, `optimize:clear`, `config:clear`, `route:clear`, `view:clear`
7. Serves the app briefly and checks `/` and `/login` return NexaCRM HTML

Job-level GitHub Actions env is only `APP_NAME`. Session, queue, cache, and mail drivers come from `.env.example` during the install path, and from `phpunit.xml` (`<env>` + `<server>`) during tests. Putting `SESSION_DRIVER=database` on the job breaks PHPUnit: Laravel reads `$_SERVER` before `$_ENV`, so HTTP tests lose the session.

Laravel Pint is installed (`laravel/pint`) and useful locally. It is **not** a blocking CI gate: `vendor/bin/pint --test` currently reports style drift across many existing files. Fixing that is a separate cleanup, not part of release validation.

There is no PHPStan/Larastan/ESLint config in this repository. None was added.

---

## Local validation

Fast (PHP only, uses your existing `vendor/`):

```bash
composer ci
```

That runs `composer validate --no-check-publish` and `php artisan test`.

Full maintainer check (Composer + npm lockfile install + Vite build + PHPUnit):

```bash
bash scripts/validate-release.sh
```

Skip the frontend if assets are already built:

```bash
SKIP_FRONTEND=1 bash scripts/validate-release.sh
```

`scripts/validate-release.sh` does **not** overwrite `.env`. Isolated migrate/seed/super-admin runs in GitHub Actions.

Optional local style pass (not required for merge):

```bash
vendor/bin/pint
```

---

## Fresh installation

Verified sequence (same as [Installation](getting-started/installation.md)):

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite   # when using SQLite
php artisan migrate
php artisan db:seed
php artisan storage:link
php artisan nexacrm:create-super-admin
npm ci && npm run build
php artisan serve
```

`composer setup` wraps install + env + migrate + **normal** seed + `permissions:sync` + `storage:link` + `npm install` + `npm run build`. It still does not create a login. Create the Super Admin afterwards.

A queue worker is not required to finish first-run or sign in. Mail can stay `MAIL_MAILER=log`.

---

## Seed behavior

| Path | Command | Creates login? | Creates Northstar demo tenant? |
|---|---|---|---|
| Normal | `php artisan db:seed` | No, unless `SETUP_SUPERADMIN_*` are both set | No (`DEMO_SEED=false`) |
| Super Admin | `php artisan nexacrm:create-super-admin` | Yes (platform operator) | No |
| Optional demo | `php artisan db:seed --class=DemoDataSeeder` | Demo personas only | Yes, requires `DEMO_SEED_PASSWORD` |

Never commit `SETUP_SUPERADMIN_PASSWORD` or `DEMO_SEED_PASSWORD`.

---

## Environment

`.env.example` is the buyer template. Local defaults (`APP_DEBUG=true`, SQLite, `MAIL_MAILER=log`) are for development.

For production, set at least:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://YOUR_DOMAIN
APP_KEY=base64:...
DB_CONNECTION=mysql
SESSION_SECURE_COOKIE=true
MAIL_MAILER=smtp
DEMO_SEED=false
SETUP_SUPERADMIN_PASSWORD=
```

The bottom of `.env.example` lists the rest of the production checklist. Leave Super Admin and demo password fields empty in any committed file.

CI copies `.env.example` and generates a key. It does not use Railway, AWS, Facebook, WhatsApp, Resend, or any production credential.

---

## Security

- Workflow permissions: read the repository only.
- No `pull_request_target`, no deployment, no `secrets.` usage.
- PHPUnit mailer is `array`; queue is `sync`; cache/session are `array` in `phpunit.xml`.
- Demo seed is not run in CI except inside tests that call `DemoDataSeeder` explicitly with the PHPUnit-only `DEMO_SEED_PASSWORD`.

---

## Media

This phase does not recapture screenshots or the product demo video.

On the current `main` tree, marketing still references the screenshot filenames in `config/marketing.php` / `resources/views/marketing/home.blade.php`, and those files exist under `public/marketing/screenshots/`. The NexaCRM pixel recapture (`nexacrm-*.png` and a re-encoded `nexacrm-product-demo.mp4`) is tracked on the separate Phase 6 branch and must not be rewritten here.

---

## Current verification

Recorded during Phase 7 (local, this Codester repository only):

| Check | Result |
|---|---|
| `composer validate --no-check-publish` | PASS |
| `npm ci` / `npm run build` | PASS (Vite 7.3.6; `public/build` gitignored) |
| Fresh migrate + normal seed + Super Admin | PASS (isolated `/tmp` SQLite; Super Admin `ci-superadmin@nexacrm.test`) |
| Demo tenant absent after normal seed | PASS (`scripts/ci-assert-normal-seed.php`) |
| Optional `DemoDataSeeder` | PASS (separate SQLite; created `northstar-solutions`; not run in CI) |
| HTTP smoke `/` and `/login` | PASS (NexaCRM HTML; no `algoscrm` / `algos.test`) |
| `php artisan test` | PASS — **887 passed** (3339 assertions) in 25.65s locally. First GitHub Actions run failed (195 tests) because job-level `SESSION_DRIVER=database` leaked into `$_SERVER`; fixed in `phpunit.xml` + workflow env. |
| GitHub Actions | PASS — [run 34975837207](https://github.com/Ezakashif/codester-release-crm/actions/runs/34975837207) on `8f9243f` (`Tests: 887 passed (3339 assertions)`) |

Pint `--test` was inspected and **fails** on existing style drift. Not used as a CI gate.

`npm audit` reported 6 vulnerabilities after `npm ci`. Packages were **not** upgraded in this phase. Composer/npm identity and versions are unchanged.
