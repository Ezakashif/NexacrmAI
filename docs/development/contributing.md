# Development notes

This is the Codester edition of NexaCRM, a commercial Laravel CRM codebase. Customize your own copy. There is no production hosting access associated with this repository.

## Local workflow

1. Follow [Installation](../getting-started/installation.md).
2. Keep feature work on a branch.
3. Run tests before opening a PR (`composer ci`). Pint remains optional.

```bash
composer ci
# including frontend:
bash scripts/validate-release.sh
```

## Tenant safety

This is a multi-tenant app. A bad change can leak Company A’s data to Company B.

- Follow [Coding standards](coding-standards.md) (CompanyScope, policies, permissions).
- Cover happy path **and** cross-tenant 403/404 in feature tests.
- Never commit `.env`, tokens, or `docs/demo-credentials.local.md`.
- Keep Super Admin routes in `routes/superadmin.php`.
- Do not use `withoutCompanyScope()` unless the code truly needs it (webhooks, platform tools), and set company context before tenant writes.

See [Multi-tenancy](../architecture/multi-tenancy.md) and [RBAC](../architecture/rbac.md).

## Checklist

- [ ] Tenant isolation considered (CompanyScope / policies)
- [ ] Permissions updated + synced if needed
- [ ] Feature tests for happy path + 403/404 cross-tenant
- [ ] No secrets committed
- [ ] Docs updated under `/docs` when behavior changes
- [ ] Migrations are MySQL-safe (index name lengths)

## Related

- [Coding standards](coding-standards.md)
- [Installation](../getting-started/installation.md)
