#!/usr/bin/env bash
# Build a Codester buyer ZIP from the NexaCRM working tree.
# Does not include .git, .env, vendor/, node_modules/, or runtime data.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

if ! command -v zip >/dev/null 2>&1; then
    echo "zip is required" >&2
    exit 1
fi

REV="$(git -C "$ROOT" rev-parse --short HEAD)"
FULL_REV="$(git -C "$ROOT" rev-parse HEAD)"
# The repository changelog is still "Unreleased"; there is no composer version field.
VERSION_LABEL="unreleased"
STAMP="$(date -u +%Y-%m-%dT%H:%M:%SZ)"
NAME="nexacrm-${VERSION_LABEL}-${REV}-codester"
STAGE="$(mktemp -d /tmp/nexacrm-codester-XXXXXX)"
DEST="${STAGE}/${NAME}"
DIST="${ROOT}/dist"
ZIP_PATH="${DIST}/${NAME}.zip"

mkdir -p "$DEST" "$DIST"

echo "==> staging ${NAME} from git-visible files"
mkdir -p "$DEST"
git -C "$ROOT" ls-files -z --cached --others --exclude-standard \
    | tar -C "$ROOT" --null -T - -cf - \
    | tar -C "$DEST" -xf -

# Remove seller-only / runtime / PaaS paths even if they are tracked.
rm -rf \
    "${DEST}/.git" \
    "${DEST}/.github" \
    "${DEST}/railway" \
    "${DEST}/vendor" \
    "${DEST}/node_modules" \
    "${DEST}/dist"
rm -f \
    "${DEST}/.env" \
    "${DEST}/.env.backup" \
    "${DEST}/.env.local" \
    "${DEST}/.env.production" \
    "${DEST}/railway.toml" \
    "${DEST}/nixpacks.toml" \
    "${DEST}/guest" \
    "${DEST}/php" \
    "${DEST}/pricing" \
    "${DEST}/database/database.sqlite" \
    "${DEST}/scripts/capture-nexacrm-demo-video.mjs" \
    "${DEST}/scripts/capture-nexacrm-screenshots.mjs" \
    "${DEST}/scripts/build-linkedin-cover.php" \
    "${DEST}/scripts/render-nexacrm-logos.php" \
    "${DEST}/scripts/build-codester-package.sh" \
    "${DEST}/scripts/codester-package.exclude"
rm -rf "${DEST}/public/hot" "${DEST}/public/build" "${DEST}/public/storage"

# Keep storage and bootstrap cache placeholders only (no logs, compiled views, uploads).
if [[ -d "${DEST}/storage" ]]; then
    find "${DEST}/storage" -type f ! -name '.gitignore' -delete
fi
if [[ -d "${DEST}/bootstrap/cache" ]]; then
    find "${DEST}/bootstrap/cache" -type f ! -name '.gitignore' -delete
fi

cat > "${DEST}/NEXACRM-PACKAGE.txt" <<EOF
Product: NexaCRM
Tagline: A Modern Multi-Tenant CRM for Growing Businesses
Composer package: ezakashif/nexacrm
Declared product version: ${VERSION_LABEL} (docs/changelog.md; composer.json has no version field)
Source revision: ${FULL_REV}
Package built (UTC): ${STAMP}
Buyer install: docs/codester-installation.md
Licenses: LICENSE (MIT) and THIRD-PARTY-NOTICES.md
EOF

fail() {
    echo "PACKAGE CHECK FAILED: $*" >&2
    rm -rf "$STAGE"
    exit 1
}

echo "==> verifying staged tree"
[[ -f "${DEST}/artisan" ]] || fail "missing artisan"
[[ -f "${DEST}/composer.json" ]] || fail "missing composer.json"
[[ -f "${DEST}/composer.lock" ]] || fail "missing composer.lock"
[[ -f "${DEST}/package.json" ]] || fail "missing package.json"
[[ -f "${DEST}/package-lock.json" ]] || fail "missing package-lock.json"
[[ -f "${DEST}/.env.example" ]] || fail "missing .env.example"
[[ -f "${DEST}/LICENSE" ]] || fail "missing LICENSE"
[[ -f "${DEST}/THIRD-PARTY-NOTICES.md" ]] || fail "missing THIRD-PARTY-NOTICES.md"
[[ -f "${DEST}/docs/codester-installation.md" ]] || fail "missing buyer install doc"
[[ -f "${DEST}/public/branding/nexacrm-logo.png" ]] || fail "missing NexaCRM logo"
[[ -f "${DEST}/public/marketing/videos/nexacrm-product-demo.mp4" ]] || fail "missing product demo video"
ls "${DEST}/public/marketing/screenshots"/nexacrm-*.png >/dev/null 2>&1 || fail "missing NexaCRM screenshots"
[[ -d "${DEST}/public/vendor/adminlte" ]] || fail "missing AdminLTE vendor assets"
[[ ! -e "${DEST}/.git" ]] || fail ".git present"
[[ ! -e "${DEST}/.env" ]] || fail ".env present"
[[ ! -d "${DEST}/vendor" ]] || fail "vendor/ present"
[[ ! -d "${DEST}/node_modules" ]] || fail "node_modules present"
[[ ! -f "${DEST}/database/database.sqlite" ]] || fail "sqlite database present"
[[ ! -d "${DEST}/.github" ]] || fail ".github present"
[[ ! -d "${DEST}/railway" ]] || fail "railway/ present"

if find "$DEST" -type f \( -name '.env' -o -name '.env.local' -o -name '.env.production' \) | grep -q .; then
    fail "secret env file found"
fi

# Real credential shapes (not the names of env vars in .env.example).
if grep -RInE --binary-files=without-match \
    'AKIA[0-9A-Z]{16}|BEGIN (RSA |OPENSSH |EC )?PRIVATE KEY|sk_live_|whsec_[A-Za-z0-9]+' \
    "$DEST" >/dev/null; then
    fail "credential-like secret material found"
fi

if grep -RInE --binary-files=without-match 'algoscrm\.com|algos\.test' "$DEST" \
    | grep -vE 'tests/|docs/(changelog|release-readiness|operations/cicd|codester-package-audit)|scripts/ci-assert|\.github/' \
    >/dev/null; then
    fail "unexpected Algos production host in buyer package"
fi

echo "==> writing ${ZIP_PATH}"
rm -f "$ZIP_PATH"
(
    cd "$STAGE"
    zip -r -X -q "$ZIP_PATH" "$NAME"
)

if unzip -Z1 "$ZIP_PATH" | grep -E '(^|/)\.env$|/\.git/|/node_modules/' >/dev/null; then
    fail "zip contains .env, .git, or node_modules"
fi
if unzip -Z1 "$ZIP_PATH" | grep -E '^[^/]+/vendor/' >/dev/null; then
    fail "zip contains Composer vendor/"
fi
echo "ZIP entries: $(unzip -Z1 "$ZIP_PATH" | wc -l)"

SIZE="$(du -h "$ZIP_PATH" | awk '{print $1}')"
echo "PACKAGE OK: ${ZIP_PATH} (${SIZE})"
echo "$ZIP_PATH" > "${STAGE}/zip-path.txt"

if [[ "${KEEP_STAGE:-0}" == "1" ]]; then
    echo "STAGE=${DEST}"
else
    rm -rf "$STAGE"
fi
