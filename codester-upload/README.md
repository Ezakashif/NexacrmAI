# Codester upload kit (NexaCRM)

Ready-to-upload seller assets and paste-ready Codester form values.  
**Built:** 18 September 2026  
**Git revision at build:** see `git rev-parse --short HEAD`

Regenerate images/ZIP anytime:

```bash
python3 scripts/build-codester-upload-assets.py
```

---

## 1. Files in this folder

| File | Codester form field | Spec |
|---|---|---|
| `nexacrm-codester-preview-800x400.png` | Preview image | **800×400** PNG (exact) |
| `nexacrm-codester-icon-200x200.png` | Icon | **200×200** PNG from brand mark (not a screenshot) |
| `nexacrm-codester-screenshots.zip` | Screenshot image set | **8** unique PNG product shots (within 3–9) |

Same preview/icon copies also live under `public/branding/` for the repo.

Screenshots included (no duplicates, no pricing page):

1. `nexacrm-dashboard.png`
2. `nexacrm-leads.png`
3. `nexacrm-customers.png`
4. `nexacrm-tasks.png`
5. `nexacrm-reports.png`
6. `nexacrm-user-management.png`
7. `nexacrm-roles-permissions.png`
8. `nexacrm-activity-log.png`

Main product ZIP remains the Phase 8 `composer package` / `scripts/build-codester-package.sh` output (not this folder).

---

## 2. Recommended Codester prices

Comparable CRM PHP scripts on Codester (observed 17 Sep 2026) listed about **$22–$155**.

**Recommended starting prices (seller may change before submit):**

| License | Recommended price | Why |
|---|---|---|
| **Regular** | **$49** | Mid of the category band for a full Laravel multi-tenant CRM with docs and media |
| **Extended** | **$129** | ~2.6× Regular for multi-project / source-in-larger-product use |

Optional development-hours field: enter **your real hours** only. Do not invent hours. Codester’s “hours × 1.2” note is guidance, not a requirement.

These are **marketplace item** prices. They are not the in-app Starter/Professional/Enterprise plan amounts shown in the product UI.

---

## 3. Author profile (paste into Codester)

Complete your Codester profile before upload (Codester rejects incomplete profiles). Suggested copy — edit name/location/links to match you:

**Display name:** Uneza (or your public seller name)

**Headline / short bio:**
> Full-stack Laravel developer shipping practical SaaS and CRM products. NexaCRM is a self-hostable multi-tenant CRM for agencies and growing teams.

**Longer about (if the form has it):**
> I build production Laravel applications with clear documentation and clean source packages. Support focuses on install issues, configuration, and documented features—not custom freelancing inside the listing price unless separately agreed.

**Skills / tags:** Laravel, PHP, SaaS, CRM, Multi-tenant, AdminLTE, MySQL

**Profile image:** use `nexacrm-codester-icon-200x200.png` or a personal photo (200×200+).

**Website / social:** add your real site or leave blank — do not invent URLs.

---

## 4. Live demo URL (seller hosting required)

Codester’s upload guide expects a **live demo** for PHP scripts, preferably iframe-friendly, with **no “buy elsewhere” CTA**. Free hosting is discouraged / treated as a rejection risk.

This agent environment cannot publish a permanent public HTTPS demo for you. What was verified locally (18 Sep 2026):

| Check | Result |
|---|---|
| App boots with demo seed | Pass (`DEMO_SEED=true`, Northstar Solutions tenant) |
| Marketing `/` and `/login` return 200 | Pass |
| `X-Frame-Options` / CSP `frame-ancestors` blocking | **None** — pages can load in an iframe |
| Algos CRM on marketing home | Not present |

### Deploy checklist (you do this on paid hosting)

1. Provision a small VPS or managed PHP host (not free shared “temporary” hosts).
2. Deploy this NexaCRM tree; point the vhost document root at `public/`.
3. Use a **dedicated** MySQL database (or SQLite only for throwaway demos).
4. Set in `.env`:
   - `APP_URL=https://your-demo-host.example`
   - `DEMO_SEED=true`
   - `DEMO_SEED_PASSWORD=` *(strong secret — never put this password on the Codester listing)*
   - Optionally `DEMO_RESET_ENABLED=true` for daily reset
5. `composer install --no-dev`, `npm ci && npm run build`, migrate + seed, `php artisan nexacrm:create-super-admin` if needed.
6. Confirm marketing site and **Try Live Demo** work.
7. Iframe test: open a blank HTML page with `<iframe src="https://your-demo-host.example/">` and confirm it loads.
8. Paste that HTTPS URL into Codester **Demo URL**.
9. On the listing, list demo emails only (`admin@demo.nexacrm.test`, etc.) — **never** the password.

Until step 8 is done, the Demo URL field remains the only technical blocker you must supply from outside this repository.

---

## 5. Upload checklist (final)

1. [x] Preview 800×400 — `nexacrm-codester-preview-800x400.png`
2. [x] Icon 200×200 — `nexacrm-codester-icon-200x200.png`
3. [x] Screenshots ZIP (8) — `nexacrm-codester-screenshots.zip`
4. [ ] Live demo HTTPS URL on **your** host (iframe-tested)
5. [ ] Regular **$49** / Extended **$129** (or your adjusted prices) entered in Codester
6. [ ] Author profile completed with the bio above
7. [ ] Main source ZIP from `composer package`
8. [ ] Listing copy from `docs/codester-listing.md`
9. [ ] Optional YouTube upload of `public/marketing/videos/nexacrm-product-demo.mp4`
