# Roadmap

Delivered and planned work for the omnichannel multi-tenant CRM.

## Delivered

| Milestone | Summary |
|---|---|
| Core CRM | Customers, leads, tasks, reports, users/roles, CSV, reminders |
| Multi-tenancy | Company scope, plans/limits, Super Admin companies |
| Super Admin platform | Plans, analytics, email templates, contact inquiries, impersonation |
| M1 Channels engine | Tables, ingestion, jobs, lead matching, generic adapter |
| M2 Channels UI | Tenant connect/test/sync/disconnect + public webhook URL |
| M3 Facebook Lead Ads | Meta verify, signatures, Graph lead fetch → CRM leads |
| WhatsApp Cloud | Inbound messages, verify, connection test |
| Inbox + WhatsApp reply | Conversation UI, assign/status, Graph send |
| Docs suite | This `/docs` tree |

## In progress / hardening

- Per-tenant Meta App Secret storage (today `META_APP_SECRET` is largely platform `.env`, with connection secret fallback)
- OAuth “Connect with Meta” instead of pasting tokens
- CI workflows in-repo (**Recommended**)

## Planned channels

| Provider | Priority notes |
|---|---|
| Instagram Lead Forms | Reuse Lead Ads patterns |
| Facebook Messenger | Inbox parity with WhatsApp |
| Instagram DM | Messaging + Inbox |
| Website Forms builder | First-class forms vs legacy website webhook |
| Public API | Versioned REST + API keys |

## Planned product

| Item | Notes |
|---|---|
| Rich media in Inbox | Images/docs beyond text placeholders |
| WhatsApp template messages | Business-initiated outside session window |
| Delivery receipt updates | Apply status webhooks to stored messages |
| Realtime Inbox | Echo/Reverb or polling |
| OpenAPI docs | When REST API lands |

## Suggested build order

1. Stabilize WhatsApp production (tokens, ngrok/prod HTTPS, monitoring)  
2. Instagram Lead Forms  
3. Messenger / Instagram DM  
4. Website Forms builder  
5. Public REST API  

## Related

- [Channels overview](channels/overview.md)
- [Changelog](changelog.md)
- [CI/CD](operations/cicd.md)
