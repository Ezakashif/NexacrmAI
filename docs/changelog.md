# Changelog

High-level product history for operators and developers.  
For git-level detail, use the repository commit / PR history.

Format inspired by [Keep a Changelog](https://keepachangelog.com/).

---

## Unreleased

### Added

- First-run Super Admin creation via `php artisan nexacrm:create-super-admin` or optional `SETUP_SUPERADMIN_*` environment variables during seed.
- Optional `DEMO_SEED` flag so the fictional demo tenant is never part of a normal install.
- Packaged NexaCRM wordmark, mark, and light-on-dark logo for Super Admin, favicon, and Open Graph fallbacks.
- Public contribution policy: PR-only workflow, no production access, and intellectual-property terms (`CONTRIBUTING.md`, `docs/development/contributing.md`).
- Pull request template and `CODEOWNERS` so outside contributions stay reviewed.
- Professional documentation tree under `/docs` (installation, architecture, channels, Super Admin, operations, user manual, roadmap).

### Changed

- `DatabaseSeeder` now seeds platform defaults only (permissions, email templates, plans). It no longer creates known example logins or sample CRM records.
- Optional demo personas use fictional `@demo.nexacrm.test` addresses.
- Email verification defaults to off on a fresh install so the app stays usable before SMTP is configured.
- `composer setup` and `post-create-project-cmd` run `php artisan storage:link`.
- Super Admin chrome and packaged public branding assets use NexaCRM identity instead of the old `algos.` mark.
- Super Admin company create requires an admin password when an admin email is provided (no silent auto-generated password).
- Website lead webhook routes to the unique tenant with an active admin instead of the empty Default Company shell.
- Channels connect form lists only providers that have a registered adapter.

### Fixed

- Fresh-install Super Admin UX around the Default Company platform shell.
- Branding for HTTP 403/404/500 pages.

---

## 2026-08 — Inbox & WhatsApp reply

### Added

- Tenant **Inbox** (`/inbox`) with conversation list/thread UI.
- WhatsApp outbound replies via Graph API.
- Conversation assign + open/pending/closed status.
- Permissions: `view.inbox`, `reply.inbox`, `assign.inbox`.

---

## 2026-07 — Channels & Meta

### Added

- Channels engine (connections, webhook events, contacts, conversations, lead channel meta).
- Channels UI for tenants (connect, test, sync, retry, disconnect, regenerate secret).
- Public webhook `GET|POST /webhooks/channels/{uuid}`.
- **Generic Webhook** adapter (signed JSON leads/messages).
- **Facebook Lead Ads** adapter (leadgen + Graph fetch).
- **WhatsApp Cloud API** inbound adapter + Meta verification.
- Config: `META_APP_SECRET`, `META_GRAPH_VERSION`, `CHANNELS_WEBHOOK_QUEUE`.
- MySQL-safe short index names + recovery for partial channel migrations.

### Fixed

- Channel route binding company-scope leak (`orWhere` grouping).
- Company Profile vs Company Settings controller (`edit()` restore after bad merge).

---

## 2026-07 — Platform & tenancy UX

### Added

- Company Profile read-only page + Company Settings editor.
- Super Admin contact inquiries for marketing form submissions.
- Soft-delete company identifier cleanup patterns.

### Changed

- Create-user flow enhancements (preset password email, create-role modal) in earlier PRs.
- Password eye icon overlap fixes on user forms.

---

## Earlier — Core CRM

### Added

- Multi-company tenancy with `CurrentCompany` / `CompanyScope`.
- Custom RBAC (`permissions:sync`, admin/sales defaults).
- Leads, customers, tasks, reports, CSV import/export.
- Lead/task reminder scheduler commands.
- Super Admin: companies, plans/limits, analytics, email templates, impersonation.
- Marketing site + website lead webhook.
- AdminLTE tenant shell.

---

## Notes

- Channel providers may appear in the UI before adapters ship; see [Channels overview](channels/overview.md) status matrix.
- CI/CD workflows are **Recommended** and documented under [CI/CD](operations/cicd.md).
