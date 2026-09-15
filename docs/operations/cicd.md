# CI/CD

Automated checks for this Codester edition. CI does **not** deploy, and it never connects to Railway, Algos CRM, or any production database.

## GitHub Actions

Workflow: [`.github/workflows/ci.yml`](../../.github/workflows/ci.yml)

Runs on pull requests and on pushes to `main`.

| Step | What it proves |
|---|---|
| `composer validate --no-check-publish` | `composer.json` / lock file are valid |
| `composer install` | PHP dependencies install from `composer.lock` |
| `npm ci` + `npm run build` | Vite marketing/app assets build from `package-lock.json` |
| `php artisan migrate --force` | A clean SQLite database can be created |
| `php artisan db:seed --force` | Normal seed stays production-safe (`DEMO_SEED=false`) |
| `php artisan nexacrm:create-super-admin` | First-run Super Admin command works without SMTP |
| `php artisan test` | Full PHPUnit suite (in-memory SQLite) |

PHP 8.2, Node 20, `contents: read` only. Details: [Release readiness](../release-readiness.md).

## Local pre-push

```bash
composer ci
# or, including the frontend lockfile install + build:
bash scripts/validate-release.sh
```

Laravel Pint is optional locally (`vendor/bin/pint`). It is not a merge gate.

## Production deploy

CI does not deploy. When you deploy your own copy, see [Deployment](../getting-started/deployment.md).
