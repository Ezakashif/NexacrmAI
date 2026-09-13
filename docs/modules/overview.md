# Modules overview

Tenant CRM feature map (company users). Super Admin is documented separately.

## Sidebar map (typical)

| Area | Menu | Permission (gate) |
|---|---|---|
| CRM | Dashboard | authenticated tenant |
| CRM | Customers | `view.customers` |
| CRM | Leads | `view.leads` |
| CRM | Inbox | `view.inbox` |
| CRM | Tasks | `view.tasks` |
| CRM | Reports | `view.reports` |
| Administration | Channels | `view.channels` |
| Administration | Website Lead Demo | `website_lead.demo` |
| Administration | Users | `view.users` |
| Administration | Roles | `view.roles` |
| Administration | Activity Log | activity log access |
| Administration | Company Profile / Settings | company settings access |
| Account | Profile | authenticated |

Exact labels live in `config/adminlte.php`.

## Module summaries

### Dashboard
`DashboardController` — company KPIs and shortcuts.

### Customers
Full resource CRUD, CSV import/export, plan limit on create.

### Leads
Resource CRUD, board/kanban updates, convert to customer, activity log entries, CSV import/export, follow-up reminders (scheduler).

### Inbox
List/show conversations from channels (notably WhatsApp). Reply (`reply.inbox`), assign (`assign.inbox`), status open/pending/closed. See [WhatsApp Cloud](../channels/whatsapp-cloud.md).

### Tasks
Resource CRUD, status updates, board updates, due/overdue reminders.

### Channels
Connect providers, webhook URL, test/sync/retry/disconnect, regenerate secret. See [Channels overview](../channels/overview.md).

### Reports
Aggregated reporting UI + export endpoints. See [Reports](reports.md).

### Users & invitations
User CRUD, activate/deactivate, invite flow, CSV import/export. Subject to plan `max_users`.

### Roles
Per-company roles and permission checkboxes (system admin role protected).

### Company profile & settings
- **Profile** (`/company`) — read-only overview  
- **Settings** (`/company/settings`) — edit identity, address, hours, logo  

### Activity logs
Tenant audit trail of important actions.

### Notifications
In-app notifications (+ email depending on preferences / templates).

### Global search
Search across tenant entities (`search.index`, `search.suggest`).

### Website Lead Demo
Internal demo of the website webhook payload for permitted users.

## CSV import / export

| Type | Import | Export |
|---|---|---|
| leads | yes | yes |
| customers | yes | yes |
| users | yes | yes |
| tasks | — | yes |

Routes under `imports.*` and `exports.*`.

## Related

- [RBAC](../architecture/rbac.md)
- [End-user manual](../user-manual/overview.md)
- [Super Admin](../super-admin/overview.md)
