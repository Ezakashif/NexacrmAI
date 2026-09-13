# Changelog

High-level product history for operators and developers.  
For git-level detail, use the repository commit / PR history.

Format inspired by [Keep a Changelog](https://keepachangelog.com/).

---

## Unreleased

### Added

- Public contribution policy: PR-only workflow, no production access, and intellectual-property terms (`CONTRIBUTING.md`, `docs/development/contributing.md`).
- Pull request template and `CODEOWNERS` so outside contributions stay reviewed.
- Professional documentation tree under `/docs` (installation, architecture, channels, Super Admin, operations, user manual, roadmap).

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
