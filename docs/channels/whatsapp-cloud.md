# WhatsApp Cloud API

Fully supported inbound WhatsApp messaging, lead/conversation creation, and Inbox replies.

## Prerequisites

- Meta Developer app with WhatsApp product  
- Phone Number ID + Cloud API access token  
- `META_APP_SECRET` in CRM `.env`  
- Public HTTPS webhook URL (ngrok for local)  
- Queue worker on `channels`

Meta links:

- https://developers.facebook.com/apps  
- App → **WhatsApp → Try it out / API Setup** (token + Phone Number ID)  
- App → **WhatsApp → Production setup / Configuration** (webhooks)  
- App Secret: **App settings → Basic**

## Connect in CRM

1. `META_APP_SECRET=...` then `php artisan config:clear`
2. **Channels → Connect channel → WhatsApp Cloud API**
3. Fields:
   - Display name  
   - **Phone Number ID** (stored as `external_page_id`)  
   - Optional **WABA ID** (`external_account_id`)  
   - **Access token**  
4. Copy **Webhook URL** + **Verify token** from the connection page

## Meta webhook

1. Callback URL = `https://YOUR_HOST/webhooks/channels/{uuid}`
2. Verify token = CRM value
3. **Verify and save**
4. Subscribe to **messages**

CRM answers Meta `GET` verification (`hub.challenge`) for WhatsApp connections.

## Inbound behavior

- Validates `X-Hub-Signature-256`
- Parses WhatsApp `messages` webhooks
- Creates/updates lead (source `whatsapp`) + conversation + inbound message
- Status-only webhooks (delivered/read) are acknowledged and ignored
- Idempotency: `whatsapp_message_{wamid}`

## Inbox reply

1. Open **CRM → Inbox**
2. Open the thread
3. Send reply (permission `reply.inbox`)
4. CRM calls Graph `POST /{phone-number-id}/messages` and stores an outbound message

Assign requires `assign.inbox` (admin by default).

## Test connection

Graph lookup on Phone Number ID (`display_phone_number`, `verified_name`).

## Local checklist

```bash
php artisan serve
php artisan queue:work --queue=channels,default
ngrok http 8000
```

Update Meta callback when the ngrok host changes.

## Troubleshooting

| Issue | Fix |
|---|---|
| Verify fails | Wrong verify token; CRM/ngrok down; using http://127.0.0.1 in Meta |
| Test connection fails | Bad token / Phone Number ID / Graph version |
| Message received in Meta logs but not CRM | Worker not running; signature secret mismatch |
| Reply errors | Closed conversation; missing token; outside WhatsApp messaging window (Meta policy) |

## Related

- [Channels overview](overview.md)
- [End-user manual — Inbox](../user-manual/overview.md#inbox)
- [Queues](../operations/queues.md)
