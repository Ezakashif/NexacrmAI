# API overview

## Today — webhooks (public HTTP)

There is **no** versioned REST API (`routes/api.php` is absent). Integrations use webhook endpoints on the web stack (CSRF exempt).

### Website leads

```http
POST /webhooks/leads/website
```

Authenticated by shared secret middleware (`WEBSITE_LEAD_WEBHOOK_SECRET`).  
See [Website Forms](../channels/website-forms.md).

### Channels

```http
GET|POST /webhooks/channels/{uuid}
```

| Method | Purpose |
|---|---|
| GET | Meta subscription verification (`hub.challenge`) for Facebook Lead Ads / WhatsApp |
| POST | Inbound events (generic HMAC or Meta `X-Hub-Signature-256`) |

Response on accept: `202` JSON `{ message, event_id, status }`.

Per-tenant isolation via connection UUID.  
See [Channels overview](../channels/overview.md) and provider docs.

### Generic Webhook contract

Documented payload shapes: [Generic Webhook](../channels/generic-webhook.md).

---

## Planned / Recommended — public REST API

Tracked as channel key `public_api` and future platform API.

**Recommended** direction:

| Area | Proposal |
|---|---|
| Transport | `routes/api.php` + Sanctum/API tokens per company |
| Resources | Leads, Customers, Tasks (CRUD subsets) |
| Authz | Same RBAC permission slugs |
| Versioning | `/api/v1` |
| Docs | OpenAPI / Scramble later |

Until built, prefer Generic Webhook for partner ingestion.

## Related

- [Public API channel (Planned)](../channels/public-api.md)
- [Roadmap](../roadmap.md)
