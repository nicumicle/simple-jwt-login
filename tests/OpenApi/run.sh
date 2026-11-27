#!/usr/bin/env bash
# Checks that the live REST responses match openapi/openapi.yaml (Schemathesis).
# Applies tests/OpenApi/settings.json, calls the API as a temporary administrator,
# then restores the settings and deletes every user created during the run.
# Never run it together with the feature or cross-site tests: it replaces the plugin settings.
#
# Env:
#   SJL_API_URL  plugin REST base URL (default: the local docker site)
#   WP_CLI       wp-cli command for that site (default: through the "wordpress" container)
# Extra arguments are passed to "schemathesis run" (for example --max-examples 50).
set -euo pipefail

SCHEMATHESIS_VERSION=4.16.0
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
API_URL="${SJL_API_URL:-http://localhost:88/wp-json/simple-jwt-login/v1}"
read -r -a WP <<< "${WP_CLI:-docker exec -i wordpress wp --allow-root --path=/var/www/html}"

OPTION=simple_jwt_login_settings
TEST_USER=sjl-openapi
TEST_PASS="$(openssl rand -hex 16)"

ORIGINAL_SETTINGS="$("${WP[@]}" option get "$OPTION" --format=json 2>/dev/null || true)"
LAST_USER_ID="$("${WP[@]}" user list --field=ID --orderby=ID --order=DESC --number=1)"

cleanup() {
    if [ -n "$ORIGINAL_SETTINGS" ]; then
        printf '%s' "$ORIGINAL_SETTINGS" | "${WP[@]}" option update "$OPTION" --format=json >/dev/null
    else
        "${WP[@]}" option delete "$OPTION" >/dev/null || true
    fi
    # The test admin plus the users registered by fuzzed POST /users calls.
    local new_users
    new_users="$("${WP[@]}" user list --field=ID | awk -v last="$LAST_USER_ID" '$1 > last')"
    if [ -n "$new_users" ]; then
        # shellcheck disable=SC2086
        "${WP[@]}" user delete $new_users --yes >/dev/null
    fi
}
trap cleanup EXIT

# The plugin stores its settings as a JSON string, so the file goes in as raw text.
"${WP[@]}" option update "$OPTION" < "$ROOT/tests/OpenApi/settings.json" >/dev/null
"${WP[@]}" user create "$TEST_USER" "$TEST_USER@example.com" --role=administrator \
    --user_pass="$TEST_PASS" --porcelain >/dev/null

get_jwt() {
    local response jwt
    response="$(curl -sS -X POST "$API_URL/auth" -H 'Content-Type: application/json' \
        -d "{\"email\":\"$TEST_USER@example.com\",\"password\":\"$TEST_PASS\"}")"
    jwt="$(printf '%s' "$response" | grep -o '"jwt":"[^"]*"' | cut -d'"' -f4 || true)"
    if [ -z "$jwt" ]; then
        echo "Could not get a JWT from $API_URL/auth: $response" >&2
        exit 1
    fi
    printf '%s' "$jwt"
}

# A revoked token, so the listRevokedTokens -> deleteRevokedToken link has a record to follow.
# Each JWT has its own random "jti" (settings.json), so revoking this one leaves $JWT valid.
curl -sS -f -o /dev/null -X POST "$API_URL/auth/revoke" -H 'Content-Type: application/json' \
    -d "{\"JWT\":\"$(get_jwt)\"}"
JWT="$(get_jwt)"

# Response-side checks only: the request-side ones (positive_data_acceptance, ...) fail on
# rules a schema cannot express (a JWT that does not decode, a reset code that does not exist).
# DELETE /users could delete the test admin and POST /auth/revoke could revoke its JWT.
# GET /autologin answers a 302 to an HTML page: followed, the page fails the JSON checks, and
# --max-redirects 0 turns the 302 into a network error (Schemathesis 4.16).
uvx --from "schemathesis==$SCHEMATHESIS_VERSION" schemathesis run "$ROOT/openapi/openapi.yaml" \
    --url "$API_URL" \
    --header "Authorization: Bearer $JWT" \
    --checks not_a_server_error,status_code_conformance,content_type_conformance,response_headers_conformance,response_schema_conformance \
    --exclude-name 'DELETE /users' \
    --exclude-name 'POST /auth/revoke' \
    --exclude-name 'GET /autologin' \
    --generation-deterministic \
    --max-examples 20 \
    "$@"
