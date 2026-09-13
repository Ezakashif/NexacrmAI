# End-user manual

Guide for tenant **Administrators** and **Sales** users of the CRM.

## Roles at a glance

| Capability | Admin | Sales (default) |
|---|---|---|
| Customers / Leads / Tasks day-to-day | Yes | Yes (with some limits) |
| Manage users & roles | Yes | No |
| Connect channels | Yes (`manage.channels`) | View only |
| Inbox view + WhatsApp reply | Yes | Yes |
| Assign inbox conversations | Yes | No |
| Company settings | Yes | No |

Exact rights can be customized under **Roles**.

---

## Sign in

1. Open your company CRM URL.
2. Sign in with email/password.
3. You land on **Dashboard**.

Forgot password uses the email reset flow (requires mail to be configured by your operator).

---

## Customers

**Customers** → add, edit, search, import/export CSV.  
Customers are won/converted accounts (also created when converting leads).

---

## Leads

**Leads** supports list and board (kanban) views.

Common actions:

- Create / edit lead  
- Move status on the board  
- Assign (if permitted)  
- Log activities  
- Convert to customer  
- Import/export CSV  

Follow-up dates drive reminder notifications when the scheduler is running.

---

## Inbox

**Inbox** shows conversations from connected messaging channels (WhatsApp today).

1. Open a conversation (unread clears on open).  
2. Read the thread; open linked lead if present.  
3. **Reply** sends a WhatsApp message when the channel supports it.  
4. Admins can **Assign** and change status (Open / Pending / Closed).  

Closed conversations must be reopened before replying.

---

## Tasks

Create and update tasks, change status, use the task board.  
Due/overdue reminders appear when enabled by your operator.

---

## Reports

Open **Reports** for company metrics. Export if you have `export.reports`.

---

## Channels (admins)

**Administration → Channels**

1. **Connect channel** — choose provider (Generic Webhook, Facebook Lead Ads, WhatsApp, …).  
2. Enter credentials shown on the form.  
3. Copy webhook URL / secrets / verify token as instructed.  
4. Use **Test connection**, **Sync**, **Disconnect**, or **Regenerate webhook secret** as needed.  

Providers marked **Adapter coming soon** cannot fully process events yet.

Detailed setup:

- [Generic Webhook](../channels/generic-webhook.md)  
- [Facebook Lead Ads](../channels/facebook-lead-ads.md)  
- [WhatsApp](../channels/whatsapp-cloud.md)  

Each company connects **its own** WhatsApp/Facebook/forms — data never mixes with other companies.

---

## Users & roles (admins)

- **Users** — invite or create users, activate/deactivate, import CSV.  
- **Roles** — adjust permissions. Do not remove critical admin access without a backup admin.

Plan limits may block adding users.

---

## Company profile & settings (admins)

- **Company Profile** — read-only overview (logo, contact, plan badges).  
- **Company Settings** — edit profile fields, regional defaults, business hours, logo.  
- Saving settings returns you to the profile.

---

## Notifications & profile

- Bell / **Notifications** — in-app alerts.  
- **Profile** — password, photo, notification preferences, sessions.

---

## Activity log

**Activity Log** shows important changes (lead updates, channel events, settings saves) for auditing.

---

## Tips

1. If a webhook test succeeds in the tool but CRM doesn’t update, ask your operator to start the **queue worker**.  
2. Never paste access tokens into chat or email.  
3. For Meta on localhost, your operator must use **ngrok** (or staging HTTPS).  

## Related

- [Modules overview](../modules/overview.md)
- [Troubleshooting](../troubleshooting.md)
