# CI/CD

> **Status: Recommended** — this repository does not currently ship a project-specific GitHub Actions workflow under `.github/` (beyond whatever may exist upstream). The following is the **recommended** pipeline to add.

## Goals

- Run automated tests on every pull request  
- Enforce code style (Pint)  
- Block merge on failure  
- Optional: deploy to staging/production on `main`

## Recommended GitHub Actions (PR)

```yaml
name: CI

on:
  pull_request:
  push:
    branches: [main]

jobs:
  tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: mbstring, pdo_sqlite, gd
          coverage: none
      - uses: actions/setup-node@v4
        with:
          node-version: '20'
          cache: npm
      - run: composer install --no-interaction --prefer-dist
      - run: cp .env.example .env
      - run: php artisan key:generate
      - run: npm ci
      - run: npm run build
      - run: vendor/bin/pint --test
      - run: php artisan test
```

## Recommended deploy job (outline)

On push to `main` (or tags):

1. SSH / container deploy  
2. `composer install --no-dev`  
3. `php artisan migrate --force`  
4. `php artisan permissions:sync`  
5. `php artisan config:cache && route:cache && view:cache`  
6. Restart PHP-FPM + `queue:restart`  

See [Deployment](../getting-started/deployment.md).

## Local pre-push checklist

```bash
vendor/bin/pint
php artisan test
```

## Related

- [Coding standards](../development/coding-standards.md)
- [Contributing](../development/contributing.md)
