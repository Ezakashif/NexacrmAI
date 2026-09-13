# Generic Webhook

Fully supported channel for signed JSON inbound leads and messages.

## When to use

- Website or middleware that can POST JSON
- Local end-to-end testing of the Channels engine
- Custom integrations without Meta

## Connect in CRM

1. Admin → **Administration → Channels → Connect channel**
2. Provider: **Generic Webhook**
3. Display name (required)
4. Optional: external account id, access token, custom webhook secret (blank = auto-generate)
5. **Connect channel**
6. Copy the **webhook secret** immediately (shown once) and the **Webhook URL**

## Endpoint

```http
POST /webhooks/channels/{uuid}
Content-Type: application/json
X-Channel-Signature: sha256={hmac_sha256_hex_of_raw_body}
```

HMAC key = connection webhook secret.  
Header may also be accepted as raw hex without prefix in some paths; prefer `sha256=` prefix.

CSRF exempt. Rate limited (`CHANNELS_WEBHOOK_RATE_LIMIT`).

## Lead payload

```json
{
  "type": "lead",
  "name": "Alex Morgan",
  "email": "alex@example.com",
  "phone": "+15550100000",
  "company": "Northline",
  "notes": "From website",
  "external_user_id": "site_123",
  "external_lead_id": "lead_456",
  "campaign": {
    "campaign_id": "camp_1",
    "form_id": "form_9"
  }
}
```

Requires `name` and at least one of `email` / `phone`.  
Lead source slug: `website`.

## Message payload

```json
{
  "type": "message",
  "external_user_id": "user_1",
  "body": "Hello",
  "phone": "+15550100000",
  "email": "alex@example.com",
  "provider_message_id": "msg_1"
}
```

Creates/updates conversation + lead matching.

## Test with curl (Git Bash)

```bash
URL='http://127.0.0.1:8000/webhooks/channels/YOUR-UUID'
SECRET='YOUR-SECRET'
BODY='{"type":"lead","name":"Alex Morgan","email":"alex@example.com","phone":"+15550100000"}'
SIG=$(printf '%s' "$BODY" | openssl dgst -sha256 -hmac "$SECRET" | awk '{print $2}')

curl -i -X POST "$URL" \
  -H "Content-Type: application/json" \
  -H "X-Channel-Signature: sha256=$SIG" \
  -d "$BODY"
```

Expect `202` + `"accepted"`. Run:

```bash
php artisan queue:work --queue=channels,default
```

Then refresh channel **Recent events** and **Leads**.

## UI Test connection

Validates HMAC signing health; does **not** create a lead.

## Troubleshooting

| Issue | Fix |
|---|---|
| 401 Invalid signature | Wrong secret; regenerate and update client |
| 202 but no lead | Queue worker not running |
| 404 Unknown connection | Wrong UUID / deleted connection |
