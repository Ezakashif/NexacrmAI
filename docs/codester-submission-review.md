# Codester submission readiness review (NexaCRM)

Phase 9 final review for manual Codester upload.  
**Review date:** 17 September 2026  
**Git revision reviewed:** `de7387f` (`main`)  
**Package built for this review:** `dist/nexacrm-unreleased-de7387f-codester.zip` (gitignored; rebuild with `composer package`)

This review does **not** change application behavior, schema, auth, tenancy, RBAC, subscriptions, marketing UI, licensing files, or production deployment.

---

## Official sources checked (17 September 2026)

| Source | URL | Used for |
|---|---|---|
| Upload guide | https://www.codester.com/info/upload | Main ZIP, docs language, 130-char short description, **800×400** preview, **200×200** icon, screenshots ZIP (3–9), demo URL, YouTube, price rule of thumb, author profile |
| Licenses | https://www.codester.com/info/licenses | Regular vs Extended permissions; licensed-not-sold; author ownership (browser-verified verbatim extract saved for this review) |
| Member terms | https://www.codester.com/info/member_terms | Clauses on licensing vs sale, buyer license, seller ownership / IP warranty (clauses 14–15 area) |
| Allowed items | https://support.codester.com/hc/en-us/articles/115000018789-What-items-are-allowed-to-be-sold-on-Codester | Scripts & code allowed; seller must have rights to sell |
| Upload blog guide | https://www.codester.com/blog/9-steps-to-successfully-submitting-your-work-to-a-digital-marketplace/ | Demo in iframe; docs required; preview/icon sizes; screenshots 3–9 |
| Soft-reject FAQ | https://support.codester.com/hc/en-us/articles/115000017365-My-item-has-been-rejected-what-now | Docs required; ZIP not RAR; no off-site buy CTA on demo; older preview size note (**1600×800**) |
| CRM category browse | https://www.codester.com/categories/322/crm-software-php-scripts | Suggested category path; public comps ~$22–$155 (observed) |

**Size conflict note:** The live upload guide and 2015 blog both specify **800×400** preview and **200×200** icon. The 2017 soft-reject article still mentions **1600×800** preview and **200×200** icon. Prefer the live upload form / upload guide (**800×400** / **200×200**) unless the form that day asks for another size. Do not stretch images.

---

## Codester marketplace licenses (live, 17 September 2026)

From https://www.codester.com/info/licenses (also consistent with member terms clause 14 summary):

- Items are **licensed, not sold**. The author keeps ownership.
- Permissions apply to purchases **regardless of purchase date** (live page wording).
- **Regular License:** one project; personal **and** commercial use; use on behalf of a client for that one project. No resale of the item on its own or as part of a project. No distribution of source files.
- **Extended License:** unlimited / multiple projects; personal and commercial use; client work across projects. Source may be distributed or sublicensed **as part of a larger project**. Item may **not** be resold as-is.

**Correction vs Phase 5 audit:** `docs/licensing-audit.md` previously summarized Regular as personal/non-commercial and Extended as a single commercial end product. That summary does **not** match the live licenses page checked on 17 September 2026. Listing copy and this review follow the live page. See the Phase 9 addendum in the licensing audit.

MIT in `LICENSE` remains the software license inside the ZIP. Codester Regular/Extended are the marketplace checkout terms. They are different instruments.

---

## Deliverables status

| Deliverable | Path | Status |
|---|---|---|
| Listing draft | `docs/codester-listing.md` | Complete (verified facts only; short descriptions ≤130 chars) |
| Media audit | `docs/codester-media-audit.md` | Complete; no recapture required |
| Buyer install guide | `docs/codester-installation.md` | Already present (Phase 8); consistent with package |
| Package audit | Removed from the repository (was Phase 8 maintainer notes; not buyer docs) | N/A |
| This submission review | `docs/codester-submission-review.md` | Complete |

---

## Product version recommendation

| Field | Value |
|---|---|
| Declared product version | **unreleased** |
| Evidence | `docs/changelog.md` has only an `Unreleased` section (no numbered release). `composer.json` has no `version` field. Package builder emits `nexacrm-unreleased-<sha>-codester.zip`. |
| Recommendation | **Keep `unreleased` for this Codester submission package.** Do **not** invent `1.0.0` / `v1.0.0` solely for marketplace cosmetics. |
| When to cut `1.0.0` | Seller explicitly tags a release, writes a numbered changelog section, and rebuilds the ZIP from that tag. |

Software stack versions (PHP, Laravel, AdminLTE, Node) may still be stated on the listing. Those are dependency versions, not the item version.

---

## ZIP ↔ listing consistency

Checked against `docs/codester-listing.md`, `docs/codester-installation.md`, `composer.json` / `composer.lock`, `package.json`, `.node-version`, and a fresh `composer package` ZIP at `de7387f`.

| Check | Result |
|---|---|
| Advertised CRM features exist in this repo | Pass (leads/customers/tasks/reports/inbox/channels/Super Admin/marketing site, etc.) |
| No planned-only features advertised as shipped | Pass (SSO, Instagram/Messenger, public REST API, Stripe/Paddle called out as **not** included) |
| PHP `^8.2`, Laravel 12 (lock **v12.62.0**), Node `>=20` / `.node-version` **22** | Pass |
| AdminLTE **v3.2.0** via `jeroennoten/laravel-adminlte` **v3.16.0** | Pass |
| Install steps match buyer guide + Phase 8 package policy (`vendor/` / `node_modules` excluded) | Pass |
| MIT + third-party notices statements match `LICENSE` / `THIRD-PARTY-NOTICES.md` | Pass |
| Demo emails only; no password on listing | Pass |
| Product name NexaCRM; no Algos CRM in listing draft | Pass |

---

## Media audit summary

| Asset | Verdict |
|---|---|
| `public/marketing/videos/nexacrm-product-demo.mp4` | Suitable for optional YouTube |
| Twelve 2880×1800 screenshots | Suitable after dedupe to ≤9 unique images |
| Exact duplicate pairs | overview=dashboard; sales-pipeline=leads — skip duplicates |
| 800×400 preview | **Missing** (blocking for upload form) |
| 200×200 icon PNG | **Missing** (SVG mark exists; export still required) |

No media recapture was performed; none was blocking for *this repository review* beyond the missing Codester form assets above.

---

## Security sanity check (ZIP + listing materials)

Scanned `dist/nexacrm-unreleased-de7387f-codester.zip` (~10.7 MiB, 1113 entries). **Secret values are not printed here.**

| Check | Result |
|---|---|
| `.env` (filled) | Absent (`.env.example` only) |
| `.git/`, Composer `vendor/`, `node_modules/`, `database.sqlite` | Absent |
| `railway.toml` / Nixpacks / Railway app hosts | Absent from ZIP paths; no `railway.app` content hits |
| AWS key / private key / Stripe live key shapes | None |
| Algos CRM buyer branding | Not in media; remaining historical Algos hostname strings are **test negative assertions or changelog notes** only — not listing copy |
| Product Hunt | No product references; Font Awesome brand stylesheet contains a generic `product-hunt` icon glyph name (third-party asset, not a NexaCRM Product Hunt integration) |
| `phpunit.xml` `DEMO_SEED_PASSWORD` | Test placeholder only; not a shipped demo login |
| Docs webhook example | Placeholder assignment in `docs/channels/website-forms.md`, not a live secret |

---

## Licensing result

| Layer | Result |
|---|---|
| Application `LICENSE` | MIT, `Copyright (c) 2026 Uneza` — unchanged |
| Composer `license` | MIT — unchanged |
| Marketplace terms to offer | Regular + Extended per live Codester licenses page |
| Custom commercial EULA | Not added (not required by public Codester docs) |
| Third-party | `THIRD-PARTY-NOTICES.md` present; Dompdf LGPL called out |

---

## Phase 9 validation

Docs-only change set. On this review machine (17 September 2026), after installing PHP 8.3 + Composer for the agent environment:

- `composer validate --no-check-publish` — pass (via `composer ci`)
- `php artisan test` — **895 passed** (3413 assertions)

No application code was modified for Phase 9.

---

## Remaining blockers (seller account / hosting — not missing repo assets)

1. [x] Create **800×400** preview — `codester-upload/nexacrm-codester-preview-800x400.png`
2. [x] Export **200×200** PNG icon — `codester-upload/nexacrm-codester-icon-200x200.png`
3. [x] Build screenshots ZIP with **3–9 unique** PNGs — `codester-upload/nexacrm-codester-screenshots.zip` (8)
4. [ ] Host a **live demo** HTTPS URL on **your** paid host; iframe-test it; no off-site purchase CTA; list demo emails only (no password). Local iframe headers verified 18 Sep 2026 (no `X-Frame-Options` / CSP `frame-ancestors` block).
5. [ ] Enter Regular/Extended **prices** on Codester (recommended **$49** / **$129** in `codester-upload/README.md`) and complete **author profile** (paste-ready bio in the same README).

---

## Ready for manual Codester submission?

**Almost — package + listing + visual assets are ready.**

- Source ZIP tooling, English docs, MIT notices, listing draft, media audit, preview, icon, and screenshots ZIP are ready.
- Upload still needs **your** live demo HTTPS URL and Codester account fields (prices + author profile). Those cannot be finished from this repository alone.

No application feature/schema change was required for these assets.
