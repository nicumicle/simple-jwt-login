#!/usr/bin/env bash
# Prepares a release: composer prepare-release X.Y.Z
#   1. Changelog.md: moves the "## Unreleased" entries to "## X.Y.Z (today)" (unless that section exists)
#   2. sets X.Y.Z in the plugin header, SIMPLE_JWT_LOGIN_VERSION, the readme "Stable tag" and openapi.yaml
#   3. copies the release into the WordPress.org readme (syncChangelog.sh)
#   4. regenerates the .pot file (composer run gen-i18n)
#   5. rebuilds download/simple-jwt-login.zip (createZip.sh)
# Never commits, tags or pushes.
set -euo pipefail

cd "$(dirname "$0")"

VERSION="${1:-}"
PLUGIN_FILE=simple-jwt-login/simple-jwt-login.php
README=simple-jwt-login/README.md
OPENAPI=openapi/openapi.yaml
CHANGELOG=Changelog.md

fail() {
    echo "prepare-release: $*" >&2
    exit 1
}

if ! [[ "$VERSION" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
    fail "usage: composer prepare-release X.Y.Z (got '$VERSION')"
fi
for tool in zip wp awk; do
    command -v "$tool" > /dev/null || fail "'$tool' is not installed"
done

tmp_dir=$(mktemp -d)
trap 'rm -rf "$tmp_dir"' EXIT

# Writes awk's output back to the file, failing when the awk program reports no match (exit 3).
rewrite() {
    local file="$1"
    shift
    awk "$@" "$file" > "$tmp_dir/rewrite" || fail "the expected line was not found in $file"
    cat "$tmp_dir/rewrite" > "$file"
}

# 1. Changelog.md
if ! grep -qF "## $VERSION (" "$CHANGELOG"; then
    release_date=$(LC_ALL=C date '+%d %B %Y')
    awk -v header="## $VERSION ($release_date)" '
        /^## Unreleased/ { print; print ""; print header; in_unreleased = 1; found = 1; next }
        in_unreleased && /^## / { in_unreleased = 0 }
        in_unreleased && /[^[:space:]]/ { has_entries = 1 }
        { print }
        END { if (!found || !has_entries) { exit 3 } }
    ' "$CHANGELOG" > "$tmp_dir/changelog" \
        || fail "no '## $VERSION (...)' section and no entries under '## Unreleased' in $CHANGELOG"
    cat "$tmp_dir/changelog" > "$CHANGELOG"
    echo "Changelog.md: Unreleased entries moved to '## $VERSION ($release_date)'."
fi
newest=$(grep -E '^## ' "$CHANGELOG" | grep -v -m1 '^## Unreleased' || true)
case "$newest" in
    "## $VERSION ("*) ;;
    *) fail "$VERSION is not the newest release in $CHANGELOG (newest: '$newest')" ;;
esac

# 2. Version strings
rewrite "$PLUGIN_FILE" -v version="$VERSION" '
    !done_header && /^[[:space:]]*Version:[[:space:]]/ { sub(/Version:.*/, "Version: " version); done_header = 1 }
    { print }
    END { if (!done_header) { exit 3 } }
'
rewrite "$PLUGIN_FILE" -v version="$VERSION" -v q="'" '
    index($0, "define(" q "SIMPLE_JWT_LOGIN_VERSION" q) == 1 {
        $0 = "define(" q "SIMPLE_JWT_LOGIN_VERSION" q ", " q version q ");"; done_define = 1
    }
    { print }
    END { if (!done_define) { exit 3 } }
'
rewrite "$README" -v version="$VERSION" '
    /^Stable tag:/ { $0 = "Stable tag: " version; done_tag = 1 }
    { print }
    END { if (!done_tag) { exit 3 } }
'
rewrite "$OPENAPI" -v version="$VERSION" '
    /^info:/ { in_info = 1 }
    in_info && /^[^[:space:]]/ && !/^info:/ { in_info = 0 }
    in_info && !done_version && /^  version:/ { $0 = "  version: " version; done_version = 1 }
    { print }
    END { if (!done_version) { exit 3 } }
'
echo "Version set to $VERSION in $PLUGIN_FILE, $README and $OPENAPI."

# 3. WordPress.org readme changelog
bash syncChangelog.sh

# 4. Translations (the .pot header carries the plugin version)
composer run gen-i18n

# 5. Zip
bash createZip.sh
unzip -p download/simple-jwt-login.zip "$PLUGIN_FILE" | grep -qE "^[[:space:]]*Version: $VERSION$" \
    || fail "download/simple-jwt-login.zip does not contain version $VERSION"

echo
echo "Release $VERSION prepared. Review the changes, run composer run check-plugin, then commit and tag."
