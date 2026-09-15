# NexaCRM licensing audit

Phase 5 investigation of the Codester edition repository. This document records evidence, not legal advice. A lawyer is required for a definitive determination of how marketplace terms interact with the MIT grant in a specific jurisdiction.

Authoritative Codester pages used (retrieved 15 September 2026):

- [Licenses](https://www.codester.com/info/licenses)
- [Member terms and conditions](https://www.codester.com/info/member_terms) (clauses 14–15)
- [What items are allowed to be sold on Codester?](https://support.codester.com/hc/en-us/articles/115000018789-What-items-are-allowed-to-be-sold-on-Codester)

---

## Current status

| Item | Value |
|---|---|
| Product | NexaCRM |
| Intended sales model | Source-code product on Codester (Regular / Extended marketplace licenses) |
| Repository software license | MIT (`LICENSE`) |
| Copyright notice in `LICENSE` | `Copyright (c) 2026 Uneza` |
| Composer package | `ezakashif/nexacrm` |
| Composer `license` field | `MIT` |
| Authors field in `composer.json` | none |

`LICENSE` and `composer.json` agree: MIT.

The README previously said the MIT file should be reviewed and replaced before a Codester sale. This audit is that review. **No replacement is required at this stage.**

There is no Livewire or Spatie package in this product. RBAC is custom.

---

## Codester requirements

Codester marketplace licensing and the MIT file inside this repository are **different instruments**. Do not treat one as automatically replacing the other.

### Marketplace license (buyer ↔ Codester listing)

From [codester.com/info/licenses](https://www.codester.com/info/licenses) (terms for licenses purchased **on or after 1 September 2026**):

- Every item is **licensed, not sold**. The **author keeps ownership**.
- **Regular License:** one end product; personal / non-commercial; end users must receive the completed product free of charge; no business, client, or revenue-generating use.
- **Extended License:** one personal or commercial end product; may be monetized, used by a business, or created for one paying client. **Not** unlimited projects. A separate Extended License is required for each additional end product.
- Both types: the buyer may modify the item to create the licensed end product; may give files to employees, contractors, or one client only for that end product; **may not** resell or redistribute the item as a stock item, template, or source file; **may not** include it in a starter kit / source-code bundle that lets others extract the item; **may not** make the item available under an open-source or other license that **conflicts with these terms**.

From [member terms, clause 14](https://www.codester.com/info/member_terms): buying a Product acquires a **non-exclusive license** from the Seller; ownership remains with the Seller; license type is chosen at purchase.

From [member terms, clause 15](https://www.codester.com/info/member_terms): the Seller warrants they **own** the IP in the Product and that it **does not infringe** third-party IP; copies of the Product must **bear a notice of ownership**.

From [allowed items](https://support.codester.com/hc/en-us/articles/115000018789-What-items-are-allowed-to-be-sold-on-Codester): Scripts & code is an allowed category; the seller must have the **rights to sell** the item.

### What Codester documentation does **not** say

These points are **unresolved** in the public pages above. They are not guessed here:

- Whether a source-code ZIP **must** omit or replace an MIT `LICENSE` file.
- Whether a seller **must** ship a custom EULA in addition to Regular/Extended.
- Whether listing copy must use specific license wording beyond the marketplace Regular/Extended choice.
- How Codester reviewers treat a repository that is MIT in GitHub while the listing is sold under Regular/Extended.

### Practical distinction for NexaCRM

| Instrument | Who it binds | What it covers |
|---|---|---|
| Codester Regular / Extended | Buyer of the Codester listing | Use of the purchased item as a marketplace product (end products, resale, commercial use) |
| MIT `LICENSE` in this repo | Recipients of **this source tree** as MIT-licensed software | Use, copy, modify, distribute, sublicense, sell **this Software**, with copyright notice preserved |
| Third-party licenses (Laravel, AdminLTE, Dompdf, Font Awesome, …) | Anyone who redistributes those components | Those components only; they are not relicensed as NexaCRM |

A Codester purchase is **not** documented as “MIT instead of Regular/Extended,” and Regular/Extended is **not** documented as deleting MIT notices that third-party packages require.

---

## Dependency audit

Inspected `composer.json`, `composer.lock` (production packages), `package.json`, and `package-lock.json`. Licenses below are the SPDX values declared in the lockfiles. Direct `require` packages were also checked against their vendor LICENSE files.

### Production PHP (direct)

| Package | Declared license | Notes |
|---|---|---|
| `laravel/framework` | MIT | Copyright Taylor Otwell |
| `laravel/tinker` | MIT | Copyright Taylor Otwell |
| `jeroennoten/laravel-adminlte` | MIT | Copyright Jeroen Noten; publishes AdminLTE 3 assets |
| `almasaeed2010/adminlte` | MIT | AdminLTE 3 (Colorlib / Bootstrap-based) |
| `barryvdh/laravel-dompdf` | MIT | Wrapper only |
| `resend/resend-php` | MIT | Optional mail transport client |

No proprietary or paid PHP packages. No AWS SDK in `require` (optional S3 via Laravel filesystem if the buyer adds it).

### Production PHP needing attention

| Package | License | Why it matters |
|---|---|---|
| `dompdf/dompdf` | **LGPL-2.1** | Copyleft on **the library**, not on NexaCRM application code. Do not relicense or strip Dompdf notices. Modifications to Dompdf itself must stay LGPL. Typical Composer use (unmodified library) is the intended distribution path. |
| `dompdf/php-font-lib` | LGPL-2.1-or-later | Transitive with Dompdf |
| `dompdf/php-svg-lib` | LGPL-3.0-or-later | Transitive with Dompdf |
| `phpoption/phpoption` | Apache-2.0 | Permissive; preserve NOTICE/LICENSE if the package is redistributed |
| `nette/schema`, `nette/utils` | BSD-3-Clause **or** GPL-2.0/3.0 | Dual-licensed; lockfile lists both. The BSD option is permissive. Do not treat NexaCRM as GPL solely because of this dual listing. |

Other production packages in `composer.lock` are MIT or BSD-3-Clause (Symfony, Guzzle, Carbon, Flysystem, League, …). No license could not be determined for a production package in the lockfile.

Dev-only PHP (PHPUnit, Pint, Sail, Collision, …) is MIT/BSD. It is not required at runtime.

### Frontend / npm

`package.json` lists **devDependencies only** (Vite, Tailwind, Alpine, Axios, Autoprefixer). Direct packages are MIT. Transitive lockfile licenses include MIT, ISC, Apache-2.0, BSD-3-Clause, 0BSD, plus:

| Transitive package | License | Role |
|---|---|---|
| `lightningcss` (and platform binaries) | MPL-2.0 | **dev** CSS toolchain for Tailwind/Vite; not application PHP |
| `caniuse-lite` | CC-BY-4.0 | **dev** browserslist data used at build time |

These do not appear as first-party runtime libraries. If a Codester ZIP includes `node_modules`, those notices must remain. The intended buyer flow is `npm install` / `npm run build`, which pulls licenses via npm.

### Shipped UI vendor (`public/vendor`)

Published AdminLTE stack with in-file copyright headers (do not strip):

| Asset | License (from file headers / FA free license page) |
|---|---|
| AdminLTE 3 JS/CSS | MIT (Colorlib / AdminLTE) |
| Bootstrap 4.6.1 | MIT |
| jQuery 3.6.0 | MIT |
| Popper.js | MIT |
| OverlayScrollbars | MIT (file header: KingSora / Rene Haas) |
| Font Awesome Free 5.15.4 | Icons **CC BY 4.0**, fonts **SIL OFL 1.1**, code **MIT** ([Font Awesome Free license](https://fontawesome.com/license/free)) |

Brand glyphs in Font Awesome Free must not be used to represent NexaCRM or other companies as if they were those brands.

Chart.js 2.7.0 is loaded from cdnjs (MIT). It is not copied into `public/vendor`. Plus Jakarta Sans / Figtree / Instrument Sans are loaded from fonts.bunny.net (typically SIL OFL families) and are **not** files in this repository.

---

## Asset audit

| Asset | Classification | Licensing note |
|---|---|---|
| `public/branding/nexacrm-*` | Original NexaCRM marks generated for this edition | Product identity; keep with the NexaCRM copyright notice |
| `public/marketing/screenshots/nexacrm-*.png` | Product screenshots recaptured from the current NexaCRM UI | No third-party stock license. Buyer-facing chrome is NexaCRM. |
| `public/marketing/videos/nexacrm-product-demo.mp4` | Product demo recording of the current NexaCRM UI | Filename and encoded frames are NexaCRM. |
| `public/vendor/**` | Framework / AdminLTE third-party | Keep existing copyright headers |
| `resources/views/welcome.blade.php` Tailwind CSS comment | Laravel default welcome remnant | MIT notice in generated CSS comment; irrelevant to buyer CRM chrome |
| Laravel Breeze-style auth views | Framework-derived, MIT | Covered by Laravel/Breeze MIT |

No stock-photo, paid-icon, or unidentified binary font files were found under `public/` outside the Font Awesome webfonts (SIL OFL).

---

## Risks

Actual risks from this audit (not hypothetical stacks):

1. **Two-license confusion.** MIT in `LICENSE` grants redistribution rights that Codester Regular/Extended restrict for **purchased marketplace copies**. Codester does not document which file “wins.” Listing and buyer docs should describe **both** without claiming MIT authorizes resale of NexaCRM as a stock item.
2. **Dompdf LGPL.** Shipping or modifying Dompdf without preserving LGPL terms would be a real compliance failure. Unmodified Composer use plus keeping vendor license files is the path this codebase already uses.
3. **Font Awesome Free terms** (CC BY / OFL / MIT split, brand-icon limits) apply to the shipped `public/vendor/fontawesome-free` tree.
4. **Seller warranty.** Codester clause 15 requires the seller to own the Product IP and not infringe third parties. Third-party MIT/BSD/Apache/LGPL/OFL components are included under **their** licenses, not as original NexaCRM code. Preserve their notices.
5. **Copyright string.** `LICENSE` says `Copyright (c) 2026 Uneza`. Other project metadata uses Uneza Kashif / `ezakashif`. That is a naming inconsistency, not evidence that MIT is wrong. Left unchanged.

No GPL-only application dependency was found. No committed secrets or buyer credentials were found in tracked license/metadata files. Remaining `algos` strings are changelog/test guards from earlier phases, not a license grant.

---

## Recommendation

**Recommended license: MIT**

Keep the existing MIT `LICENSE` file and the Composer `"license": "MIT"` field.

Why:

- Codester’s public docs define Regular/Extended for **marketplace purchases**. They do not instruct sellers to delete MIT from a Laravel source tree.
- Replacing MIT with a custom commercial EULA in this phase would be invented legal wording, which this audit is not authorized to draft.
- Direct application dependencies are MIT-licensed Laravel / AdminLTE / Tinker / Resend. MIT is the matching application license.
- LGPL Dompdf remains LGPL regardless of whether NexaCRM is MIT or proprietary; changing NexaCRM’s license does not remove that obligation.
- The `LICENSE` copyright notice already satisfies Codester’s “notice of ownership” requirement in form (author keeps ownership; year and name are present).

Option B (another OSI license) is not indicated: Apache-2.0 would not fix the Codester redistribution tension; GPL/LGPL on **NexaCRM itself** would be a larger, unjustified copyleft expansion.

Option C (proprietary / custom commercial) is a **possible later packaging choice** if the author wants the ZIP’s top-level grant to match Codester’s no-resale rules more closely than MIT does. That requires a lawyer. It is **not** implemented here.

Composer package name `ezakashif/nexacrm` is unchanged.

---

## Required changes

No repository license changes are required at this stage.

`LICENSE` is untouched. `composer.json` `name` and `license` are untouched.

Buyer-facing documentation should:

- State that this source tree is MIT-licensed.
- State that a Codester purchase is also subject to the Regular or Extended license selected at checkout.
- Point at this audit for third-party notices (especially Dompdf LGPL and Font Awesome Free).
- Defer listing copy, ZIP contents, and any proprietary EULA to a later packaging phase.

The Codester source ZIP (Phase 8) **does not** include `vendor/`. Buyers run `composer install`. Third-party notices for that path are in `THIRD-PARTY-NOTICES.md`. `public/vendor` AdminLTE/Font Awesome files remain in the ZIP with their in-file copyright headers. Buyer-facing screenshots and the product demo video were recaptured in a later media pass so they show NexaCRM chrome.
