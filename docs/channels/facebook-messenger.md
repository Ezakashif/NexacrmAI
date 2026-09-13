# Facebook Messenger

> **Status: Planned** — listed in `config/channels.php` (`facebook_messenger`) but **no adapter** is registered in `ChannelServiceProvider`.

## Intended behavior (planned)

- Meta Page Messenger webhooks  
- Inbound messages → Inbox conversations  
- Optional outbound reply (similar to WhatsApp)  
- Per-tenant Page token + verify token  

## What you can do today

- Create a Channels UI row may be possible if the provider is enabled in config, but processing will not fully work (**Adapter coming soon**).
- For messaging now, use **WhatsApp Cloud API**.
- For custom bots, use **Generic Webhook** message payloads.

## Related

- [Channels overview](overview.md)
- [WhatsApp Cloud API](whatsapp-cloud.md)
- [Roadmap](../roadmap.md)
