# Super Admin guide

Platform console for operators who manage all tenant companies.

## Access

- Middleware: `auth`, `active`, `superadmin`
- URL prefix: `/superadmin`
- Route names: `superadmin.*`
- Layout: Super Admin (`sa-app`), not AdminLTE tenant chrome
- Super Admins typically have `company_id = null` and are redirected away from tenant CRM by `EnsureCompanyContext`

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
3. Audit impersonation / company changes via activity logs  
4. Keep platform settings changes rare and documented  

## Related

- [Multi-tenancy](../architecture/multi-tenancy.md)
- [Deployment](../getting-started/deployment.md)
- [Scheduler](../operations/scheduler.md)
