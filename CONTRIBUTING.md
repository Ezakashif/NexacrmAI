# Contributing

Thank you for offering to help. This CRM accepts outside work **only through pull requests**. Contributors do not get production access, Super Admin, or direct push to `main`.

The full policy (who can contribute, PR workflow, secrets, tenancy, and intellectual property) is:

**[docs/development/contributing.md](docs/development/contributing.md)**

By opening a pull request you agree to that document.

## Non-negotiables

1. **PR-only.** Fork or use a feature branch; open a PR against `main`. The maintainer reviews and merges.
2. **No live access.** Do not ask for Railway, SSH, database, `.env`, Meta/WhatsApp tokens, or Super Admin on production.
3. **Tenant safety.** Preserve `CompanyScope` / policies; add cross-tenant 403/404 tests.
4. **IP.** Submitting a PR grants the repository owner a perpetual, worldwide, irrevocable, royalty-free license to use, modify, sublicense, and relicense your contribution as part of this CRM. It does not entitle you to payment, equity, or production access.

If you cannot agree, do not open a pull request. If you can, start from [Installation](docs/getting-started/installation.md) and the checklist in the full policy.
