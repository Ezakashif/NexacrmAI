# Codester media audit (NexaCRM)

Audit of buyer-facing screenshots and the product demo video in this repository.  
**Date:** 17 September 2026  
**Revision:** `de7387f`

No recapture was performed. Duplicates and missing Codester preview/icon sizes are documented instead of redesigned.

Codester screenshot ZIP rule (live upload guide, 17 September 2026): **3–9** JPG/PNG images, product-only crops, professional sample data.

---

## Product demo video

| Field | Value |
|---|---|
| File | `public/marketing/videos/nexacrm-product-demo.mp4` |
| Size | ~1.9 MiB |
| Branding | `nexacrm.` throughout |
| Algos CRM | Not shown |
| Railway | Not shown |
| Address bar / localhost | Not shown (browser chrome cropped) |
| Secrets | Sign-in password is masked. Email shown: `admin@demo.nexacrm.test` (demo persona, not a shipped password) |
| Content | Marketing home → login → dashboard → leads Kanban → customers → tasks Kanban → reports → users → roles → dashboard |
| Codester use | Suitable as a YouTube upload (Video URL is additional). Not a substitute for the 800×400 preview image. |

**Verdict:** suitable. Do not publish the demo password on Codester even though it is not visible in the file.

---

## Branding stills (not CRM screenshots)

| File | Size (px) | What it is | Codester screenshot ZIP? | Notes |
|---|---|---|---|---|
| `public/branding/nexacrm-logo.png` | 860×200 | Wordmark | No | Too wide/short for 800×400 preview; do not stretch |
| `public/branding/nexacrm-logo-light.png` | 860×200 | Light wordmark | No | Same |
| `public/branding/nexacrm-mark.svg` | SVG | Icon mark | No | Source for a **200×200 PNG icon** you still need to export |
| `public/branding/nexacrm-linkedin-cover.png` | 1584×396 | Cover with tagline | No | Not 800×400; stretching would violate Codester’s “don’t stretch” note |

---

## Marketing screenshots (`public/marketing/screenshots/`)

All twelve files are **2880×1800 PNG**, NexaCRM tenant or marketing chrome, fictional Northstar-style data, `nexacrm.` wordmark. No Algos branding, no Railway, no visible `localhost` / `127.0.0.1`, no API keys, no `.env`. Customer emails use `*.demo.nexacrm.test`. Activity-log IPs are documentation-range (`203.0.113.24`), not a private office network.

Two pairs are **byte-identical duplicates** (same dimensions and file size):

- `nexacrm-overview.png` = `nexacrm-dashboard.png` (323197 bytes)
- `nexacrm-sales-pipeline.png` = `nexacrm-leads.png` (322788 bytes)

`nexacrm-analytics.png` is a cropped/scrolled dashboard (charts + pending tasks), not a distinct module.

Reports KPIs in `nexacrm-reports.png` (e.g. 12 leads) do not match dashboard totals in `nexacrm-dashboard.png` (18 leads). That is a capture-timing inconsistency in demo data, not a product defect. Non-blocking.

### Per-file table

| Filename | Demonstrates | Suitable for Codester screenshot ZIP? | Issue |
|---|---|---|---|
| `nexacrm-dashboard.png` | Tenant dashboard, KPIs, follow-ups, tasks | **Yes — include** | None blocking |
| `nexacrm-overview.png` | Same as dashboard | **No — skip** | Exact duplicate of dashboard |
| `nexacrm-leads.png` | Lead Kanban (New → Lost) | **Yes — include** | None blocking |
| `nexacrm-sales-pipeline.png` | Same as leads | **No — skip** | Exact duplicate of leads |
| `nexacrm-customers.png` | Customer list, CSV actions | **Yes — include** | Fictional `*.demo.nexacrm.test` emails (acceptable sample data) |
| `nexacrm-tasks.png` | Task Kanban | **Yes — include** | None blocking |
| `nexacrm-reports.png` | Reports, donut charts, export | **Yes — include** | Demo totals differ from dashboard capture |
| `nexacrm-analytics.png` | Dashboard charts (partial) | Optional / skip if at 9-image cap | Overlaps dashboard |
| `nexacrm-activity-log.png` | Activity log | **Yes — include** | TEST-NET IPs only |
| `nexacrm-roles-permissions.png` | Roles list | **Yes — include** | None blocking |
| `nexacrm-user-management.png` | Users; demo persona emails | **Yes — include** | Shows `admin@demo.nexacrm.test` etc. (demo-only; no passwords) |
| `nexacrm-pricing.png` | Public marketing pricing page | **Yes — include as marketing**, or skip | Shows **in-app** Starter $0 / Growth $49 / Enterprise $149. These are **not** the Codester item price. Plans are Super Admin–configured. `config/marketing.php` still has different placeholder amounts — do not treat either set as marketplace pricing. |

No screenshot in this set shows Super Admin, Inbox, Channels, or the website-lead demo page. That is not a blocker; do not claim those screens exist.

---

## Recommended 9-image screenshot ZIP (max allowed)

1. `nexacrm-dashboard.png`
2. `nexacrm-leads.png`
3. `nexacrm-customers.png`
4. `nexacrm-tasks.png`
5. `nexacrm-reports.png`
6. `nexacrm-user-management.png`
7. `nexacrm-roles-permissions.png`
8. `nexacrm-activity-log.png`
9. `nexacrm-pricing.png` (only if the listing text states these figures are example in-app plans)

If you prefer not to show dollar amounts that buyers might confuse with the Codester price, replace #9 with `nexacrm-analytics.png` or stop at 8 images.

Package that ZIP **separately** from the product source ZIP. Codester’s form asks for a screenshots archive in addition to the main download.

---

## Missing Codester listing images

| Required by live upload guide | Present in repo? |
|---|---|
| 800×400 preview | **Yes** — `public/branding/nexacrm-codester-preview-800x400.png` (and `codester-upload/` copy) |
| 200×200 icon (not a screenshot) | **Yes** — `public/branding/nexacrm-codester-icon-200x200.png` (exported from SVG mark) |

Regenerate with `python3 scripts/build-codester-upload-assets.py`. Screenshots ZIP for the form: `codester-upload/nexacrm-codester-screenshots.zip` (8 unique images).

---

## Overall media verdict

| Question | Result |
|---|---|
| Shows NexaCRM branding | Yes |
| Shows Algos CRM | No |
| Shows Railway | No |
| Shows localhost URLs | Not in the cropped frames |
| Exposes credentials/secrets | No (demo emails only) |
| Consistent with current UI | Yes, Northstar demo tenant chrome |
| Represents real modules | Yes for dashboard, leads, customers, tasks, reports, users, roles, activity, marketing pricing |
| Ready for Codester upload assets | **Yes** for preview, icon, and screenshots ZIP. Video suitable for YouTube. Live demo URL still requires seller hosting. |
