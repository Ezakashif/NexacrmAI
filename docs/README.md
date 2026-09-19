# NexaCRM Documentation

Professional documentation for the multi-tenant Laravel CRM platform.

> **Audience:** developers, operators, Super Admins, and tenant end users.  
> **Stack:** PHP 8.2+, Laravel 12, AdminLTE 3, database queues, custom RBAC (no Spatie).  
> **Diagrams:** Mermaid (rendered by GitHub, many IDEs, and most Markdown viewers).

---

## Table of contents

### Getting started
| Doc | Description |
|---|---|
| [Installation](getting-started/installation.md) | Local setup (Composer, env, migrate, seed, assets) |
| [Codester installation](codester-installation.md) | Buyer ZIP extract → install (no Git required) |
| [Configuration](getting-started/configuration.md) | Environment variables reference |
| [Deployment](getting-started/deployment.md) | Production deploy checklist (web, queue, scheduler) |

### Architecture
| Doc | Description |
|---|---|
| [Architecture overview](architecture/overview.md) | System layers, request flow, key packages |
| [Multi-tenancy](architecture/multi-tenancy.md) | Companies, `CurrentCompany`, scopes, fail-closed |
| [RBAC](architecture/rbac.md) | Permissions registry, roles, gates, sync |

### Product modules
| Doc | Description |
|---|---|
| [Modules overview](modules/overview.md) | Tenant CRM feature map |
| [Reports](modules/reports.md) | Reporting & export |

### Channels (omnichannel)
| Doc | Description |
|---|---|
| [Channels overview](channels/overview.md) | Engine, adapters, multi-tenant model |
| [Generic Webhook](channels/generic-webhook.md) | Signed JSON inbound (fully supported) |
| [Facebook Lead Ads](channels/facebook-lead-ads.md) | Meta leadgen → CRM leads (fully supported) |
| [WhatsApp Cloud API](channels/whatsapp-cloud.md) | Inbound messages + Inbox reply (fully supported) |
| [Website Forms](channels/website-forms.md) | Existing website lead webhook + planned builder |
| [Facebook Messenger](channels/facebook-messenger.md) | **Planned** |
| [Instagram Lead Forms](channels/instagram-lead-forms.md) | **Planned** |
| [Instagram DM](channels/instagram-dm.md) | **Planned** |
| [Public API channel](channels/public-api.md) | **Planned** |

### Super Admin
| Doc | Description |
|---|---|
| [Super Admin guide](super-admin/overview.md) | Platform console: companies, plans, settings, inquiries |

### Operations
| Doc | Description |
|---|---|
| [Queues](operations/queues.md) | Database queue, `channels` queue, workers |
| [Scheduler](operations/scheduler.md) | Cron / `schedule:run`, reminders, pruning |

### Development
| Doc | Description |
|---|---|
| [Coding standards](development/coding-standards.md) | Pint, Laravel conventions, tenancy/RBAC rules |
| [Contributing](development/contributing.md) | Public policy: who can contribute, PR-only, no production access, IP |

### End users
| Doc | Description |
|---|---|
| [End-user manual](user-manual/overview.md) | Day-to-day CRM usage for admins & sales |

### Reference
| Doc | Description |
|---|---|
| [API](api/overview.md) | Public webhooks today; **Recommended** REST API later |
| [Troubleshooting](troubleshooting.md) | Common failures & fixes |
| [Roadmap](roadmap.md) | Delivered milestones & planned work |
| [Changelog](changelog.md) | High-level product history |
| [Licensing audit](licensing-audit.md) | MIT vs Codester marketplace terms, third-party notices |

---

## Quick start (local)

```bash
composer setup
php artisan nexacrm:create-super-admin
php artisan serve
# other terminal:
php artisan queue:work --queue=channels,default
```

`composer setup` migrates, seeds platform defaults, and runs `php artisan storage:link`. It does not create a login. See [Installation](getting-started/installation.md).

Open `APP_URL` (default `http://localhost:8000`).

### In-app docs viewer

While logged in (tenant CRM or Super Admin), open:

- `/docs` — documentation home  
- `/docs/getting-started/installation` — example page  

Sidebar: **Account → Documentation** (CRM) or **Documentation** (Super Admin).

On any docs page you can:

- **PDF** — download the current page  
- **Download all** — download the full documentation as one PDF (`/docs/pdf`)

---

## Support notes

- Tenant CRM UI: AdminLTE (`crm-app` layout).
- Super Admin UI: separate `sa-app` layout under `/superadmin`.
- There is **no** `routes/api.php` yet; inbound integrations use webhooks on `routes/web.php` (CSRF-exempt).
- Channel providers listed in `config/channels.php` may appear in the UI before an adapter is registered — see [Channels overview](channels/overview.md).
