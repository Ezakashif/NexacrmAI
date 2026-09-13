# Public API channel

> **Status: Planned** — `public_api` appears in the channel catalog; there is **no** REST API surface (`routes/api.php` does not exist) and **no** adapter.

## Intended behavior (planned)

- Authenticated public HTTP API for creating leads / events per tenant  
- API keys or OAuth per company  
- Rate limiting & audit logs  

## What you can do today

- **Generic Webhook** with HMAC signatures per connection  
- **Website lead webhook** for a shared server-to-server form endpoint  

See also [API overview](../api/overview.md).

## Related

- [Generic Webhook](generic-webhook.md)
- [Roadmap](../roadmap.md)
