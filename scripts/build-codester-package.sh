#!/usr/bin/env bash
# Build a Codester buyer ZIP from the NexaCRM working tree.
# Does not include .git, .env, vendor/, node_modules/, or runtime data.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

native_path() {
    if command -v cygpath >/dev/null 2>&1; then
        cygpath -m "$1"
    else
        printf '%s\n' "$1"
    fi
}

win_path() {
    if command -v cygpath >/dev/null 2>&1; then
        cygpath -w "$1"
    else
        printf '%s\n' "$1"
    fi
}

php_bin() {
    if command -v php >/dev/null 2>&1; then
        command -v php
        return 0
    fi
    return 1
}

php_has_zip() {
    local php
    php="$(php_bin)" || return 1
    "$php" -r 'exit(class_exists("ZipArchive") ? 0 : 1);' >/dev/null 2>&1
}

powershell_bin() {
    if command -v powershell.exe >/dev/null 2>&1; then
        command -v powershell.exe
        return 0
    fi
    if command -v powershell >/dev/null 2>&1; then
        command -v powershell
        return 0
    fi
    return 1
}

seven_zip_bin() {
    if command -v 7z >/dev/null 2>&1; then
        command -v 7z
        return 0
    fi
    if command -v 7z.exe >/dev/null 2>&1; then
        command -v 7z.exe
        return 0
    fi
    return 1
}

resolve_zip_method() {
    local forced="${CODESTER_ZIP_METHOD:-auto}"
    case "$forced" in
        zip|php|7z|powershell)
            printf '%s\n' "$forced"
            return 0
            ;;
        auto) ;;
        *)
            echo "Unknown CODESTER_ZIP_METHOD=${forced} (use auto, zip, php, 7z, or powershell)" >&2
            exit 1
            ;;
    esac

    if command -v zip >/dev/null 2>&1; then
        echo zip
        return 0
    fi
    if php_has_zip; then
        echo php
        return 0
    fi
    if seven_zip_bin >/dev/null; then
        echo 7z
        return 0
    fi
    if powershell_bin >/dev/null; then
        echo powershell
        return 0
    fi

    echo "Cannot create a ZIP." >&2
    echo "" >&2
    echo "Git for Windows / Git Bash does not ship the Info-ZIP \"zip\" command." >&2
    echo "Use one of these, then re-run: composer package" >&2
    echo "  1. Enable PHP zip (php -m should list zip). On XAMPP, uncomment extension=zip in php.ini." >&2
    echo "  2. Install zip, for example: pacman -S zip   or   scoop install zip" >&2
    echo "  3. Install 7-Zip and add 7z.exe to PATH" >&2
    echo "" >&2
    echo "The ZIP is written to dist/ which is gitignored. It is never committed to GitHub." >&2
    exit 1
}

create_zip() {
    local dest="$1"
    local zip_path="$2"
    local stage="$3"
    local name="$4"

    rm -f "$zip_path"

    case "$ZIP_METHOD" in
        zip)
            (
                cd "$stage"
                zip -r -X -q "$zip_path" "$name"
            )
            ;;
        php)
            "$(php_bin)" "${ROOT}/scripts/codester-zip.php" create "$(native_path "$dest")" "$(native_path "$zip_path")"
            ;;
        7z)
            (
                cd "$stage"
                "$(seven_zip_bin)" a -tzip -bd "$zip_path" "$name" >/dev/null
            )
            ;;
        powershell)
            "$(powershell_bin)" -NoProfile -NonInteractive -ExecutionPolicy Bypass \
                -File "$(win_path "${ROOT}/scripts/codester-zip.ps1")" \
                -Action create \
                -Path "$(win_path "$dest")" \
                -Destination "$(win_path "$zip_path")"
            ;;
        *)
            echo "internal error: unknown zip method ${ZIP_METHOD}" >&2
            exit 1
            ;;
    esac
}

list_zip_entries() {
    local zip_path="$1"

    if command -v unzip >/dev/null 2>&1; then
        unzip -Z1 "$zip_path"
        return 0
    fi

    case "$ZIP_METHOD" in
        php)
            "$(php_bin)" "${ROOT}/scripts/codester-zip.php" list "$(native_path "$zip_path")"
            return 0
            ;;
        powershell)
            "$(powershell_bin)" -NoProfile -NonInteractive -ExecutionPolicy Bypass \
                -File "$(win_path "${ROOT}/scripts/codester-zip.ps1")" \
                -Action list \
                -Path "$(win_path "$zip_path")"
            return 0
            ;;
        7z)
            "$(seven_zip_bin)" l -ba -slt "$zip_path" | awk '/^Path = /{sub(/^Path = /,""); print}'
            return 0
            ;;
    esac

    if php_has_zip; then
        "$(php_bin)" "${ROOT}/scripts/codester-zip.php" list "$(native_path "$zip_path")"
        return 0
    fi
    if powershell_bin >/dev/null; then
        "$(powershell_bin)" -NoProfile -NonInteractive -ExecutionPolicy Bypass \
            -File "$(win_path "${ROOT}/scripts/codester-zip.ps1")" \
            -Action list \
            -Path "$(win_path "$zip_path")"
        return 0
    fi
    if seven_zip_bin >/dev/null; then
        "$(seven_zip_bin)" l -ba -slt "$zip_path" | awk '/^Path = /{sub(/^Path = /,""); print}'
        return 0
    fi

    echo "Cannot list ZIP entries (install unzip, or enable PHP zip)." >&2
    exit 1
}

ZIP_METHOD="$(resolve_zip_method)"
echo "==> zip method: ${ZIP_METHOD}"

REV="$(git -C "$ROOT" rev-parse --short HEAD)"
FULL_REV="$(git -C "$ROOT" rev-parse HEAD)"
# The repository changelog is still "Unreleased"; there is no composer version field.
VERSION_LABEL="unreleased"
STAMP="$(date -u +%Y-%m-%dT%H:%M:%SZ)"
NAME="nexacrm-${VERSION_LABEL}-${REV}-codester"
STAGE="$(mktemp -d "${TMPDIR:-/tmp}/nexacrm-codester-XXXXXX")"
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
    "${DEST}/scripts/codester-zip.php" \
    "${DEST}/scripts/codester-zip.ps1" \
    "${DEST}/scripts/codester-package.exclude" \
    "${DEST}/docs/codester-package-audit.md" \
    "${DEST}/docs/release-readiness.md" \
    "${DEST}/docs/operations/cicd.md"
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

[[ ! -f "${DEST}/docs/codester-package-audit.md" ]] || fail "maintainer package audit doc must not ship"
[[ ! -f "${DEST}/docs/release-readiness.md" ]] || fail "maintainer release-readiness doc must not ship"
[[ ! -f "${DEST}/docs/operations/cicd.md" ]] || fail "maintainer CI/CD doc must not ship"

if grep -RInE --binary-files=without-match 'algoscrm\.com|algos\.test' "$DEST" \
    | grep -vE 'tests/|docs/changelog\.md|scripts/ci-assert' \
    >/dev/null; then
    fail "unexpected Algos production host in buyer package"
fi

echo "==> writing ${ZIP_PATH}"
create_zip "$DEST" "$ZIP_PATH" "$STAGE" "$NAME"

ENTRIES="$(list_zip_entries "$ZIP_PATH")"
if ! printf '%s\n' "$ENTRIES" | grep -E '(^|/)\.env\.example$' >/dev/null; then
    fail "zip missing .env.example"
fi
if printf '%s\n' "$ENTRIES" | grep -E '(^|/)\.env$|/\.git/|/node_modules/' >/dev/null; then
    fail "zip contains .env, .git, or node_modules"
fi
if printf '%s\n' "$ENTRIES" | grep -E '^[^/]+/vendor/' >/dev/null; then
    fail "zip contains Composer vendor/"
fi
echo "ZIP entries: $(printf '%s\n' "$ENTRIES" | wc -l)"

SIZE="$(du -h "$ZIP_PATH" | awk '{print $1}')"
echo "PACKAGE OK: ${ZIP_PATH} (${SIZE})"
echo "$ZIP_PATH" > "${STAGE}/zip-path.txt"

if [[ "${KEEP_STAGE:-0}" == "1" ]]; then
    echo "STAGE=${DEST}"
else
    rm -rf "$STAGE"
fi
