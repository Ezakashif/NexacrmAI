# Super Admin guide

Platform console for operators who manage all tenant companies.

## Access

- Middleware: `auth`, `active`, `superadmin`
- URL prefix: `/superadmin`
- Route names: `superadmin.*`
- Layout: Super Admin (`sa-app`), not AdminLTE tenant chrome
- Super Admins typically have `company_id = null` and are redirected away from tenant CRM by `EnsureCompanyContext`

On a fresh install there is no Super Admin until you create one:

```bash
php artisan nexacrm:create-super-admin
```

Public registration is disabled by default, so this command (or `SETUP_SUPERADMIN_*` during seed) is the first-run path. It works over SSH on a normal VPS or shared host and does not require a web installer. See [Installation](../getting-started/installation.md).

## Capabilities

| Area | Routes (examples) | Purpose |
|---|---|---|
| Dashboard | `superadmin.dashboard` | Platform KPIs |
| Search | `superadmin.search.*` | Cross-tenant search |
| Analytics | `superadmin.analytics.*` | Companies / leads / customers analytics |
| Companies | `superadmin.companies.*` | CRUD, status, restore soft-deletes, PDF, CSV import/export |
| Impersonation | `superadmin.companies.impersonate`, `impersonation.leave` | Act as tenant admin |
| Plans | `superadmin.plans.*` | Plans, limits, import/export, duplicate, bulk |
| Super Admins | `superadmin.super-admins.*` | Manage platform operators |
| Settings | `superadmin.settings.*` | Platform branding, announcement |
| Account | `superadmin.account.*`, `superadmin.password.update` | Change your own Super Admin password |
| Email templates | `superadmin.email-templates.*` | Template CRUD, preview, test send |
| Contact inquiries | `superadmin.contact-inquiries.*` | Marketing contact/demo form submissions |
| Notifications | `superadmin.notifications.*` | Platform notifications |

## Companies & soft delete

Soft-deleted companies can be viewed/restored from Super Admin. Identifier cleanup commands exist for releasing emails/slugs (`companies:release-deleted-identifiers`).

## Plans & limits

Plans define `max_users` / `max_leads` / `max_customers` (nullable = unlimited) and optional `plan_limits` rows. Tenant creates are blocked by `PlanLimitService` when exceeded.

## Contact inquiries

Marketing site contact submissions are persisted for Super Admins (not the same as tenant Channels). Manage under **Contact inquiries**.

## Operational heartbeats

Scheduler writes `scheduler_last_run_at` platform setting every five minutes — use it to verify cron is alive.

## Security practices

1. Minimize Super Admin accounts  
2. Prefer impersonation over sharing tenant passwords  
3. Change your Super Admin password from **Account** after first login and whenever it may have been shared  
4. Audit impersonation / company changes via activity logs  
5. Keep platform settings changes rare and documented  

## Related

- [Multi-tenancy](../architecture/multi-tenancy.md)
- [Deployment](../getting-started/deployment.md)
- [Scheduler](../operations/scheduler.md)
