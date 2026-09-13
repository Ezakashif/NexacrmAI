# Website Forms

## Current (supported) path — website lead webhook

Independent of the Channels UI “Website Forms” provider. Server-to-server POST creates a tenant lead.

### Configure

```env
WEBSITE_LEAD_WEBHOOK_SECRET=long-random-secret
WEBSITE_LEAD_CREATED_BY_EMAIL=admin@example.com
WEBSITE_LEAD_RATE_LIMIT=10
```

### Endpoint

```http
POST /webhooks/leads/website
```

Middleware: `website-lead-webhook` + throttle `website-leads`. CSRF exempt.

**Never** put the webhook secret in browser JavaScript. Call from your backend only.

### Demo UI

Users with `website_lead.demo` can open **Administration → Website Lead Demo** to exercise the flow.

### vs Channels Generic Webhook

| | Website lead webhook | Generic Webhook channel |
|---|---|---|
| Config | `.env` secret | Per-connection secret |
| Multi-tenant routing | Implementation-specific / shared endpoint | Per-company UUID URL |
| UI connection row | No | Yes under Channels |

For new multi-tenant form backends, prefer **Generic Webhook** per company unless you standardize on the dedicated website endpoint.

---

## Planned — Website Forms channel builder

Listed in `config/channels.php` as `website_form` but **no channel adapter is registered** yet.

**Planned** capabilities:

- Form builder / embed snippets per tenant  
- First-class Channels connection + health  
- Unified event log with other providers  

Until then, the provider may appear in the connect dropdown as **Adapter coming soon**.

## Related

- [Generic Webhook](generic-webhook.md)
- [Channels overview](overview.md)
- [Roadmap](../roadmap.md)
