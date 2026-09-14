## Summary

<!-- What does this PR change, and why? Keep the scope to one bug or feature. -->

## Checklist

- [ ] Tenant isolation considered (CompanyScope / policies)
- [ ] Permissions updated + synced if needed
- [ ] Feature tests for happy path + 403/404 cross-tenant
- [ ] No secrets, `.env` files, or tokens committed
- [ ] Docs updated under `/docs` when behavior changes
- [ ] Migrations are MySQL-safe (index name lengths)
