# Coding standards

Conventions for PHP/Laravel work in this CRM.

## Style

- Use **Laravel Pint** (default Laravel preset; no custom `pint.json` in repo).
- Run before commit when you touch PHP:

```bash
vendor/bin/pint
```

Pint is **not** a blocking CI check. The current tree still has style drift; do not treat `vendor/bin/pint --test` as a merge requirement until that cleanup is an explicit task. GitHub CI runs Composer validation, the Vite build, migrations, normal seed, and PHPUnit.

## PHP / Laravel

- PHP 8.2+ features are fine (enums, readonly DTOs already used in Channels).
- Prefer constructor property promotion and explicit return types on new code.
- Keep controllers thin; put domain logic in `app/Services`.
- Use Form Requests for non-trivial validation.
- Authorize via policies / `$this->authorize` / `@can` / permission middleware.

## Tenancy rules

1. Models with tenant data should use `BelongsToCompany`.
2. Do not bypass `CompanyScope` unless required (webhooks, Super Admin tools).
3. After `withoutCompanyScope()` lookups, set `CurrentCompany` before tenant writes.
4. Never trust client-provided `company_id` for authz.

## RBAC rules

1. New abilities → `config/permissions.php` then `php artisan permissions:sync`.
2. Slugs are `{action}.{module}`.
3. Update `RbacSeeder` defaults when a permission should ship to `sales`/`admin`.
4. Cover allow + deny in Feature tests.

## Channels rules

1. New providers need an adapter implementing `ChannelAdapter` **and** registration in `ChannelServiceProvider`.
2. Listing a key in `config/channels.php` alone only exposes UI — mark docs as Planned until registered.
3. Webhook HTTP handlers must stay fast; enqueue work on `channels`.
4. Store secrets encrypted on `channel_connections`.

## Testing

- PHPUnit 11 (`tests/Unit`, `tests/Feature`).
- Prefer Feature tests for HTTP + tenancy + permissions.
- Fake HTTP for Meta Graph (`Http::fake`).
- `Queue::fake()` when asserting dispatch without processing.

```bash
php artisan test
# or
composer test
```

## Comments

- Do not narrate obvious code.
- Comment only non-obvious constraints (Meta quirks, tenancy escape hatches, migration MySQL limits).

## Related

- [Contributing](contributing.md)
- [Architecture](../architecture/overview.md)
