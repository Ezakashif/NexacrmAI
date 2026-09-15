# Codester package audit

How the NexaCRM Codester source ZIP is built. This is not listing copy (Phase 9) and not a production deploy.

## Package identity

| Item | Value |
|---|---|
| Product | NexaCRM |
| Tagline | A Modern Multi-Tenant CRM for Growing Businesses |
| Composer package | `ezakashif/nexacrm` |
| Application license | MIT (`LICENSE`) |
| Declared product version | **unreleased** — `docs/changelog.md` has no numbered release; `composer.json` has no `version` field. No `v1.0.0` was invented. |
| Source `main` at packaging | includes Phase 7 `6a9762323e064ab7f88b1433a54fdf296db6c290` |
| Builder | `scripts/build-codester-package.sh` |
| Exclude list | `scripts/codester-package.exclude` |

`NEXACRM-PACKAGE.txt` inside each ZIP records the git revision and UTC build time.

Codester’s public upload guide ([codester.com/info/upload](https://www.codester.com/info/upload)) requires a main `.zip` with documentation, English docs, and a clean layout. It does **not** require `vendor/` or `node_modules/` to be pre-bundled. Seller pages ask for a clean archive and removal of temporary/private files.

## Inclusion / exclusion policy

### Included

Laravel application source the buyer must have to install:

- `app/`, `bootstrap/`, `config/`, `database/` (migrations, factories, seeders — not local sqlite files)
- `lang/`, `public/` (including `public/vendor` AdminLTE stack, `public/branding/nexacrm-*`, `public/marketing/screenshots/nexacrm-*.png`, `public/marketing/videos/nexacrm-product-demo.mp4`)
- `resources/`, `routes/`, `storage/` **placeholders** (`.gitignore` only)
- `tests/` and `phpunit.xml` (this is a developer/agency source product; `php artisan test` is a supported check)
- `artisan`, `composer.json`, `composer.lock`, `package.json`, `package-lock.json`
- `vite.config.js`, `tailwind.config.js`, `postcss.config.js`, `.editorconfig`, `.gitattributes`, `.gitignore`, `.node-version`
- `.env.example`
- `LICENSE`, `THIRD-PARTY-NOTICES.md`, `README.md`, `CONTRIBUTING.md`, `docs/`
- Buyer install guide: `docs/codester-installation.md`
- Maintainer scripts that are not packaging internals: `scripts/validate-release.sh`, `scripts/ci-assert-normal-seed.php`

### Excluded (and why)

| Path | Why |
|---|---|
| `.git/` | Git history is not a Codester deliverable |
| `.env` and secret env files | Credentials; buyers copy `.env.example` |
| `vendor/` | See vendor decision below |
| `node_modules/` | See Node decision below |
| `public/build/`, `public/hot` | Generated Vite output; buyer runs `npm run build` |
| `database/database.sqlite` | Local/runtime data |
| `storage` logs, compiled views, uploads | Runtime data |
| `.github/` | Seller CI, not a buyer install requirement |
| `railway/`, `railway.toml`, `nixpacks.toml` | PaaS leftovers; not required to install NexaCRM |
| `scripts/capture-nexacrm-*.mjs` and logo renderers | Maintainer media recapture, not install |
| Package builder itself | Seller-only |
| Empty root files `guest`, `php`, `pricing` | Accidental tracked empty files |
| IDE/OS junk, coverage, phpunit cache | Development artifacts |

### `vendor/` decision

**Excluded.**

Evidence:

- `composer.json` `setup` and `docs/getting-started/installation.md` / README all tell the developer to run `composer install` from `composer.lock`.
- Codester does not document a requirement to pre-ship Composer vendor trees.
- Phase 5 noted that if `vendor/` *were* shipped, each package `LICENSE` must remain. The buyer flow is `composer install`, which writes those files under `vendor/`.
- Dompdf LGPL obligations are described in `THIRD-PARTY-NOTICES.md` and `docs/licensing-audit.md`. Unmodified Composer install is the intended distribution path.
- Shipping `vendor/` would enlarge the ZIP and duplicate what Composer already reproduces from the lockfile.

### `node_modules/` decision

**Excluded.** Buyers run `npm ci` then `npm run build`. AdminLTE/jQuery/Font Awesome for the tenant UI are already under `public/vendor/` and **are** included.

## Licensing

- NexaCRM MIT `LICENSE` is included. Composer `"license": "MIT"` and package name `ezakashif/nexacrm` are unchanged.
- No custom commercial EULA was added.
- `THIRD-PARTY-NOTICES.md` preserves Dompdf LGPL, Font Awesome Free (CC BY / OFL / MIT), Apache/BSD, and AdminLTE/MIT stack notes.
- `docs/licensing-audit.md` is included for the Phase 5 evidence trail.

## Environment / secret notes

`.env.example` uses empty passwords and `hello@example.com` placeholders.

`phpunit.xml` sets `DEMO_SEED_PASSWORD=phpunit-only-demo-secret` with `force="true"` so tests can run DemoDataSeeder. That value is a **test placeholder**, not a production credential, and is not a login for a shipped demo tenant (normal seed does not create demo users).

Tests and changelog may mention `algos.` / `algos.test` as **negative assertions** or historical notes. Those are not production endpoints.

## Installation verification

Recorded from `dist/nexacrm-unreleased-901ade3-codester.zip` extracted to `/tmp/nexacrm-buyer-extract` (clean tree, not Algos/Railway):

| Check | Result |
|---|---|
| ZIP generated | PASS — `nexacrm-unreleased-901ade3-codester.zip` (10,726,739 bytes / 11 MiB) |
| Extract: no `.git` / `.env` / Composer `vendor/` / `node_modules` | PASS |
| Required Laravel + NexaCRM media + docs present | PASS |
| `composer install` | PASS (from `composer.lock`) |
| `php artisan migrate` | PASS (SQLite) |
| Normal `db:seed` (no demo tenant) | PASS (`scripts/ci-assert-normal-seed.php`) |
| `nexacrm:create-super-admin` | PASS (`buyer-superadmin@nexacrm.test`; throwaway local password, not shipped) |
| Super Admin `Auth::attempt` | PASS |
| Tenant provision via `CompanyProvisioner` (same service as Super Admin → Companies) | PASS (`buyer-tenant-co` / `tenant-admin@nexacrm.test`) |
| Tenant admin `Auth::attempt` | PASS |
| `npm ci` / `npm run build` | PASS (`public/build/manifest.json`) |
| HTTP `/` and `/login` | PASS (NexaCRM HTML; no `algoscrm` / `algos.test`) |
| `php artisan test` | PASS — **888 passed** (3373 assertions) |
| Secrets scan | PASS — only empty `.env.example` keys, Laravel `env('AWS_SECRET_ACCESS_KEY')` config, docs placeholders, changelog/test **negative** Algos guards. No Product Hunt. `phpunit.xml` `DEMO_SEED_PASSWORD=phpunit-only-demo-secret` is a test placeholder, not a shipped login. |

## Package artifact

| Item | Value |
|---|---|
| Filename | `nexacrm-unreleased-901ade3-codester.zip` |
| Location | `dist/` on the machine that ran `composer package` (gitignored; **not committed**) |
| Size | 10,726,739 bytes (~11 MiB) |
| Git revision inside ZIP | `901ade3ce0e6f4fb8e36a914bba14af6f5b098b0` (`NEXACRM-PACKAGE.txt`) |
