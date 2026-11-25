#!/usr/bin/env bash
# Copies the newest release of Changelog.md into the WordPress.org readme (simple-jwt-login/README.md):
# - "== Changelog ==": the release entry, added first or replaced in place. "### Security" /
#   "### Breaking changes" bullets get a "Security: " / "Breaking: " prefix, other bullets stay as they are;
# - "== Upgrade Notice ==": the release's "**Upgrade notice:** ..." line, added first or replaced.
# Older readme entries are never touched.
# Usage: syncChangelog.sh          update the readme
#        syncChangelog.sh --check  exit 1 when the readme is out of date (changes nothing)
set -euo pipefail

cd "$(dirname "$0")"

CHANGELOG=Changelog.md
README=simple-jwt-login/README.md

tmp_dir=$(mktemp -d)
trap 'rm -rf "$tmp_dir"' EXIT

# 1. The newest release entry, in readme format, and its upgrade notice.
awk -v notice_file="$tmp_dir/notice.txt" '
    /^## / {
        if ($0 ~ /^## Unreleased/) { next }
        if (in_release) { exit }
        print "= " substr($0, 4) " ="
        in_release = 1
        next
    }
    !in_release { next }
    # Headings, blank lines and the upgrade notice end a bullet.
    /^#+ / || /^[[:space:]]*$/ || /^\*\*Upgrade notice:\*\*/ { in_bullet = 0 }
    /^### / {
        section = substr($0, 5)
        prefix = ""
        if (section == "Security") { prefix = "Security: " }
        if (section == "Breaking changes") { prefix = "Breaking: " }
        next
    }
    /^\*\*Upgrade notice:\*\*/ {
        sub(/^\*\*Upgrade notice:\*\*[[:space:]]*/, "")
        print > notice_file
        next
    }
    /^- / {
        text = substr($0, 3)
        if (prefix != "" && index(text, prefix) != 1) { text = prefix text }
        print "- " text
        in_bullet = 1
        next
    }
    /^[[:space:]]+[^[:space:]]/ || in_bullet { print }
' "$CHANGELOG" > "$tmp_dir/entry.txt"

title=$(head -n 1 "$tmp_dir/entry.txt")
if [ -z "$title" ] || [ "$(wc -l < "$tmp_dir/entry.txt")" -lt 2 ]; then
    echo "No release with bullets found in $CHANGELOG" >&2
    exit 1
fi
notice=$(cat "$tmp_dir/notice.txt" 2>/dev/null || true)

# 2. Rebuild the readme. An entry runs from its "= X (date) =" line to the next blank line.
awk -v entry="$tmp_dir/entry.txt" -v title="$title" -v notice="$notice" '
    function print_entry(   line) {
        while ((getline line < entry) > 0) { print line }
        close(entry)
    }
    /^== / {
        section = $0
        if (section == "== Changelog ==") { has_changelog = 1 }
    }

    # Replace the existing entry or notice of this release.
    skipping && /^[[:space:]]*$/ { skipping = 0 }
    skipping { next }
    section == "== Changelog ==" && $0 == title {
        print_entry(); skipping = 1; entry_done = 1; next
    }
    section == "== Upgrade Notice ==" && notice != "" && $0 == title {
        print; print notice; skipping = 1; notice_done = 1; next
    }

    # Otherwise add it before the first entry.
    section == "== Changelog ==" && !entry_done && /^= / && !entry_present {
        print_entry(); print ""; entry_done = 1
    }
    section == "== Upgrade Notice ==" && notice != "" && !notice_done && /^= / && !notice_present {
        print title; print notice; print ""; notice_done = 1
    }
    { print }
' entry_present="$(awk -v t="$title" '/^== /{s=$0} s=="== Changelog ==" && $0==t {print 1; exit}' "$README")" \
  notice_present="$(awk -v t="$title" '/^== /{s=$0} s=="== Upgrade Notice ==" && $0==t {print 1; exit}' "$README")" \
  "$README" > "$tmp_dir/README.md"

if ! grep -qxF "$title" "$tmp_dir/README.md"; then
    echo "Could not place \"$title\": no entry under \"== Changelog ==\" in $README" >&2
    exit 1
fi

if [ "${1:-}" = "--check" ]; then
    if ! diff -u "$README" "$tmp_dir/README.md"; then
        echo "$README is out of date: run composer run sync-changelog" >&2
        exit 1
    fi
    echo "$README changelog is up to date."
    exit 0
fi

cp "$tmp_dir/README.md" "$README"
echo "$README: \"$title\" synced from $CHANGELOG."
