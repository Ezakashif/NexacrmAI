## Summary

<!-- What does this PR change, and why? Keep the scope to one bug or feature. -->

## Contribution policy

- [ ] I agree to `CONTRIBUTING.md` (PR-only, no production access, IP terms)
- [ ] This PR does not include secrets, `.env` files, tokens, or production credentials
- [ ] I am not requesting hosting, database, Super Admin, or live tenant access

## Checklist

- [ ] Tenant isolation considered (CompanyScope / policies)
- [ ] Permissions updated + synced if needed
- [ ] Feature tests for happy path + 403/404 cross-tenant
- [ ] Docs updated under `/docs` when behavior changes
- [ ] Migrations are MySQL-safe (index name lengths)
