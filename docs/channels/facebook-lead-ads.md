# Facebook Lead Ads

Fully supported Meta Lead Ads → CRM lead ingestion.

## Prerequisites

- Meta app with Lead Ads / webhooks capability  
- Facebook Page + lead form  
- Page access token  
- `META_APP_SECRET` in CRM `.env` (same Meta app)

Meta links:

- Apps: https://developers.facebook.com/apps  
- App Secret: **App settings → Basic**  
- Webhooks: app dashboard → Webhooks / Page subscriptions for `leadgen`

## Connect in CRM

1. Set `META_APP_SECRET` and `php artisan config:clear`
2. **Channels → Connect channel → Facebook Lead Ads**
3. Enter:
   - Display name  
   - **Facebook Page ID** (`external_page_id`)  
   - **Page access token**  
4. Connect, then copy **Webhook URL** + **Verify token**

## Meta webhook setup

1. Callback URL: public HTTPS URL to  
   `/webhooks/channels/{uuid}`  
   (use ngrok for local)
2. Verify token: from CRM connection page  
3. Subscribe to **leadgen** for the Page  
4. CRM handles `GET` hub.challenge verification automatically for this provider

## Processing behavior

1. Validate `X-Hub-Signature-256` with `META_APP_SECRET` (fallback: connection webhook secret)
2. Parse `leadgen` changes; skip other pages if Page ID is set on the connection
3. Fetch lead field data from Graph API with the page token
4. Create/update CRM lead (source `facebook`) + `lead_channel_meta` (campaign/ad/form/page ids)
5. Idempotency key: `facebook_leadgen_{leadgen_id}`

## Test connection

Calls Graph `GET /{page-id}?fields=id,name` with the stored token.

## Queue

Inbound POSTs are queued on `channels`. Keep a worker running.

## Security

- Rotate App Secret / page tokens if exposed  
- Prefer short-lived tokens only for demos; use long-lived / system user tokens in production  

## Related

- [Channels overview](overview.md)
- [WhatsApp Cloud API](whatsapp-cloud.md) (shares Meta app secret pattern)
