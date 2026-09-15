# Third-party notices

NexaCRM (`LICENSE`, MIT; Composer package `ezakashif/nexacrm`) is original application code.

This file lists **third-party** components that ship with or are pulled in by a normal install. It is not legal advice. Evidence and Codester-marketplace notes are in [docs/licensing-audit.md](docs/licensing-audit.md).

Do not relicense these components. Do not strip copyright headers from `public/vendor`.

## PHP (Composer)

Installed when the buyer runs `composer install`. License files then live under `vendor/<vendor>/<package>/`.

| Component | License | Notes |
|---|---|---|
| Laravel framework, Tinker | MIT | Copyright Taylor Otwell |
| jeroennoten/laravel-adminlte | MIT | Publishes AdminLTE 3 assets |
| almasaeed2010/adminlte | MIT | AdminLTE 3 |
| barryvdh/laravel-dompdf | MIT | Wrapper only |
| **dompdf/dompdf** | **LGPL-2.1** | PDF engine. Copyleft applies to **Dompdf**, not to NexaCRM application code. Keep Dompdf notices. Do not relicense Dompdf. |
| dompdf/php-font-lib | LGPL-2.1-or-later | Transitive with Dompdf |
| dompdf/php-svg-lib | LGPL-3.0-or-later | Transitive with Dompdf |
| resend/resend-php | MIT | Optional mail transport |
| phpoption/phpoption | Apache-2.0 | Transitive |
| nette/schema, nette/utils | BSD-3-Clause or GPL-2.0/3.0 (dual) | Transitive; lockfile lists both |

Other production packages in `composer.lock` are MIT or BSD-3-Clause (Symfony, Guzzle, Carbon, Flysystem, League, and similar).

## Shipped UI (`public/vendor`)

These files are already in the source package (tenant AdminLTE chrome). Headers in the files are authoritative.

| Asset | License |
|---|---|
| AdminLTE 3 JS/CSS | MIT |
| Bootstrap 4.6.1 | MIT |
| jQuery 3.6.0 | MIT |
| Popper.js | MIT |
| OverlayScrollbars | MIT |
| Font Awesome Free 5.15.4 | Icons CC BY 4.0, fonts SIL OFL 1.1, code MIT ([Font Awesome Free](https://fontawesome.com/license/free)) |

Do not use Font Awesome brand glyphs to imply NexaCRM is those brands.

Chart.js and some marketing fonts are loaded from CDNs at runtime; they are not copied into this repository.

## Frontend toolchain (npm)

`package.json` lists **devDependencies** (Vite, Tailwind, Alpine, Axios). The buyer runs `npm ci` / `npm run build`. Those packages are not shipped as `node_modules` in the Codester ZIP.

Direct npm packages are MIT. Transitive toolchain licenses include MIT, ISC, Apache-2.0, BSD-3-Clause, MPL-2.0 (`lightningcss`, build-time), and CC-BY-4.0 (`caniuse-lite`, build-time).

## NexaCRM original assets

`public/branding/nexacrm-*`, `public/marketing/screenshots/nexacrm-*.png`, and `public/marketing/videos/nexacrm-product-demo.mp4` are NexaCRM product identity and screenshots, not third-party stock.
