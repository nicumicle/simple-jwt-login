# Simple JWT Login - AI Abilities (MCP)

[< Back to the README](../README.md) · [WP-CLI reference](CLI.md)

Simple JWT Login registers its actions with the [WordPress Abilities API](https://developer.wordpress.org/apis/abilities-api/), so AI agents can manage the plugin for you. With the [MCP Adapter](https://github.com/WordPress/mcp-adapter) plugin installed, the abilities are available to any MCP client (Claude, Cursor, ChatGPT, ...).

**Requirements:** WordPress 6.9 or newer. On older versions the feature does nothing, and the settings page shows a warning.

## Setup

1. Install and activate the [MCP Adapter](https://github.com/WordPress/mcp-adapter/releases) plugin. Skip this step if you only call the abilities through the REST API (see [Calling an ability](#calling-an-ability)).
2. Go to **Simple JWT Login > Integrations > Third Party Integrations > AI Abilities (MCP)**, turn on **Enable** (off by default, so nothing is registered) and choose the **AI access** level. The page shows whether the MCP Adapter is installed and active.
   * **Read only** - AI agents can view settings, API keys, webhooks, logs, analyze the audit log and run the diagnostics.
   * **Write only** - AI agents can change settings, revoke API keys and remove webhooks.
   * **Read & write** - both.

   Then choose the **AI permissions**. They cover the riskiest actions and are all off by default:
   * **Allow viewing secrets** - secrets are returned in clear text instead of `********` (audit log output stays masked).
   * **Allow changing roles** - `register.new_user_profile` (role of new users), `register.allowed_user_meta` and `auth_codes.codes.*.role` become writable. Even then, AI agents can never set `administrator` or any role with administrator capabilities (`manage_options`, `promote_users`, `edit_users`, plugin management, ...) as the new user role or an auth code role.

   The security settings (`general.security`: safe redirects, trusted proxy IP headers) can never be changed by AI agents.
   * **Allow changing authentication providers** - the OAuth providers (`integrations.oauth.<provider>`, for example the Auth0 domain or a client ID), the JWT rules (trusted issuers) and how a JWT is matched to a user (`login.login_by`, `login.login_by_parameter`, `login.iss_whitelist`) and the Two-Factor integration (`integrations.3rdparty.two_factor`) become writable.
   * **Allow creating API keys** - registers `create-api-key` (the agent receives a working key).
   * **Allow adding webhooks** - registers `add-webhook` (a webhook sends plugin events to any HTTPS URL).

   Changing roles or authentication providers, creating API keys and adding webhooks also need Write access. Turning a permission off takes effect immediately; after turning one on, restart running MCP sessions (abilities are registered when the session starts).
3. Connect your MCP client. The integration page shows ready-to-copy commands for your site. There are two ways:

   **Option 1 - WP-CLI, locally or over SSH (recommended).** The client starts WP-CLI on the server, so only someone who can already run commands there can connect. Use key-based SSH login (the client cannot answer a password prompt):

   ```bash
   # Same machine
   claude mcp add my-site -- wp mcp-adapter serve --server=mcp-adapter-default-server --user=admin --path=/path/to/wordpress
   # Remote server over SSH
   claude mcp add my-site -- ssh user@your-server wp mcp-adapter serve --server=mcp-adapter-default-server --user=admin --path=/path/to/wordpress
   # WordPress in Docker
   claude mcp add my-site -- docker exec -i wordpress wp mcp-adapter serve --server=mcp-adapter-default-server --user=admin --allow-root --path=/var/www/html
   ```

   <details>
   <summary>Other AI clients (Codex CLI, Cursor, Claude Desktop, Gemini CLI, VS Code / GitHub Copilot)</summary>

   Same machine; for SSH or Docker, start the same command through `ssh` or `docker exec -i` as above:

   ```bash
   # Codex CLI
   codex mcp add my-site -- wp mcp-adapter serve --server=mcp-adapter-default-server --user=admin --path=/path/to/wordpress
   ```

   Cursor (`.cursor/mcp.json`), Claude Desktop (`claude_desktop_config.json`), Gemini CLI (`~/.gemini/settings.json`) and other clients that use `mcpServers`:

   ```json
   {
       "mcpServers": {
           "my-site": {
               "command": "wp",
               "args": ["mcp-adapter", "serve", "--server=mcp-adapter-default-server", "--user=admin", "--path=/path/to/wordpress"]
           }
       }
   }
   ```

   VS Code / GitHub Copilot (`.vscode/mcp.json`):

   ```json
   {
       "servers": {
           "my-site": {
               "type": "stdio",
               "command": "wp",
               "args": ["mcp-adapter", "serve", "--server=mcp-adapter-default-server", "--user=admin", "--path=/path/to/wordpress"]
           }
       }
   }
   ```

   </details>

   **Option 2 - HTTP (no SSH access, for example shared hosting).** Create an Application Password in **Users > Profile > Application Passwords** for the administrator the AI will act as, then connect to the MCP endpoint `https://example.com/wp-json/mcp/mcp-adapter-default-server` with Basic authentication. Only do this over HTTPS:

   ```bash
   claude mcp add --transport http my-site https://example.com/wp-json/mcp/mcp-adapter-default-server \
     --header "Authorization: Basic $(printf '%s' 'admin:APPLICATION_PASSWORD' | base64 | tr -d '\n')"
   ```

   <details>
   <summary>Other AI clients (Gemini CLI, Codex CLI, Cursor, VS Code / GitHub Copilot)</summary>

   Other AI clients use the same endpoint and header. `BASE64_CREDENTIALS` below is the output of `printf '%s' 'admin:APPLICATION_PASSWORD' | base64`:

   ```bash
   # Gemini CLI
   gemini mcp add --transport http --header "Authorization: Basic BASE64_CREDENTIALS" my-site https://example.com/wp-json/mcp/mcp-adapter-default-server
   ```

   Codex CLI (`~/.codex/config.toml`):

   ```toml
   [mcp_servers.my-site]
   url = "https://example.com/wp-json/mcp/mcp-adapter-default-server"
   http_headers = { "Authorization" = "Basic BASE64_CREDENTIALS" }
   ```

   Cursor (`.cursor/mcp.json`) and other clients that use `mcpServers` with a `url`:

   ```json
   {
       "mcpServers": {
           "my-site": {
               "url": "https://example.com/wp-json/mcp/mcp-adapter-default-server",
               "headers": { "Authorization": "Basic BASE64_CREDENTIALS" }
           }
       }
   }
   ```

   VS Code / GitHub Copilot (`.vscode/mcp.json`):

   ```json
   {
       "servers": {
           "my-site": {
               "type": "http",
               "url": "https://example.com/wp-json/mcp/mcp-adapter-default-server",
               "headers": { "Authorization": "Basic BASE64_CREDENTIALS" }
           }
       }
   }
   ```

   </details>

   > [!WARNING]
   > These config files hold a working administrator credential: keep them out of version control.

   Use an Application Password, not your login password. If you get `401 Sorry, you are not allowed to do that` with correct credentials, check that Application Passwords are available: WordPress only enables them over HTTPS or when `WP_ENVIRONMENT_TYPE` is `local`. Some hosts also strip the `Authorization` header before it reaches WordPress.

   On a live site, start with **Read only** access, use a dedicated administrator account for AI agents and confirm write actions in your AI client.

## Calling an ability

Every ability is named `simple-jwt-login/<name>` and takes a JSON object as input. There are three ways to call one:

**From an AI client (MCP):** ask in plain words - the agent picks the ability and builds the input. The prompts in the examples below are what you can type.

**REST API:** read abilities are run with `GET` (input as `input[<field>]` query parameters), write abilities with `POST` (input as `{"input": {...}}`). Authenticate as an administrator, for example with an Application Password:

```bash
# Read ability
curl -u admin:APPLICATION_PASSWORD \
  "https://example.com/wp-json/wp-abilities/v1/abilities/simple-jwt-login/get-settings/run?input[section]=login"

# Write ability
curl -u admin:APPLICATION_PASSWORD -X POST \
  -H "Content-Type: application/json" \
  -d '{"input":{"section":"login","values":{"enabled":true}}}' \
  "https://example.com/wp-json/wp-abilities/v1/abilities/simple-jwt-login/update-settings/run"
```

**PHP (other plugins):**

```php
$ability = wp_get_ability('simple-jwt-login/diagnose');
$report  = $ability ? $ability->execute(array('warnings_only' => true)) : null;
if (is_wp_error($report)) {
    error_log($report->get_error_message());
}
```

List what is registered for the current user with `GET /wp-json/wp-abilities/v1/abilities` (abilities the user can not run are hidden).

## Abilities reference

| Ability | Access | Needs |
|---------|--------|-------|
| [`get-settings`](#get-settings) | Read | |
| [`diagnose`](#diagnose) | Read | |
| [`security-report`](#security-report) | Read | |
| [`explain-error`](#explain-error) | Read | Any logged-in user |
| [`inspect-jwt`](#inspect-jwt) | Read | |
| [`list-api-keys`](#list-api-keys) | Read | |
| [`get-audit-log`](#get-audit-log) | Read | |
| [`analyze-audit-log`](#analyze-audit-log) | Read | |
| [`list-webhooks`](#list-webhooks) | Read | |
| [`get-webhook-logs`](#get-webhook-logs) | Read | |
| [`update-settings`](#update-settings) | Write | Some sections: **Allow changing roles** / **Allow changing authentication providers** |
| [`create-api-key`](#create-api-key) | Write | **Allow creating API keys** |
| [`revoke-api-key`](#revoke-api-key) | Write | |
| [`add-webhook`](#add-webhook) | Write | **Allow adding webhooks** |
| [`remove-webhook`](#remove-webhook) | Write | |

All abilities need an administrator (`manage_options`), except `explain-error`. Sections accepted by `section`: `api_keys`, `audit_log`, `auth_codes`, `authorization`, `cors`, `delete_user`, `general`, `hooks`, `integrations`, `jwt_rules`, `login`, `protect_endpoint`, `register`, `reset_password`, `theme`, `webhooks`.

### Settings

#### `get-settings`

Returns all settings, or one section. Secrets are masked as `********` unless **Allow viewing secrets** is on (RSA keys are then returned base64 encoded, as stored). The response also lists the secret paths, the paths that are read-only right now (`read_only_paths`) and the granted AI permissions (`ai_permissions`).

| Input | Type | Required | Description |
|-------|------|----------|-------------|
| `section` | string | No | One section. Omit it for every section |

```json
{"section": "authorization"}
```

> "Show me the authentication settings." · "Which settings can you change?"

#### `update-settings`

Changes settings in **one** section. Pass only the keys to change, nested like the output of `get-settings`. Only existing keys can be changed, values are cast to the stored type, and the result is validated exactly like a save from the admin page: on error nothing is saved. The response has the `diff` (old and new values, secrets masked) and the section's new `settings`.

| Input | Type | Required | Description |
|-------|------|----------|-------------|
| `section` | string | Yes | The section to change |
| `values` | object | Yes | Nested map of keys to their new values |

```json
{"section": "authorization", "values": {"enabled": true, "ttl": 60}}
```

```json
{"section": "cors", "values": {"enabled": 1, "allow_origin_enabled": true, "allow_origin": "https://app.example.com"}}
```

Never writable: secrets, `_schema_version`, `general.security`, `webhooks.items` (use the webhook abilities) and the AI Abilities settings. Need **Allow changing roles**: `register.new_user_profile`, `register.allowed_user_meta`, `auth_codes.codes.*.role` (never an administrator role). Need **Allow changing authentication providers**: `integrations.oauth.<provider>`, `jwt_rules`, `login.login_by`, `login.login_by_parameter`, `login.iss_whitelist`, `integrations.3rdparty.two_factor`.

> "Enable the /auth endpoint and set the JWT lifetime to one hour." · "Turn on CORS for https://app.example.com."

<details>
<summary><b>Editing auth codes and JWT rules</b></summary>

These lists mix secret and non-secret fields, so `update-settings` takes operations instead of a new list. `match` must equal the item's current non-secret values; otherwise nothing is saved, so a stale index never changes the wrong item:

```json
{
  "section": "auth_codes",
  "values": {
    "codes": [
      {"index": 0, "match": {"role": "subscriber"}, "set": {"role": "editor"}},
      {"index": 1, "match": {"role": "author"}, "remove": true}
    ]
  }
}
```

New auth codes and JWT rules always need a secret, so they can only be added from the admin page (or `wp jwt auth-code add`). Other lists (custom claims, protect-endpoint rules) are sent in full; lists stored side by side, such as `rules_url` / `rules_type` / `rules_roles`, must keep the same length.

</details>

### Diagnostics

#### `diagnose`

Health report: plugin, WordPress and PHP versions, the full URL of every endpoint with whether it is enabled and needs an auth code, how JWTs are read and verified (only whether a key is set), the valid role names, and misconfiguration warnings with the settings paths to fix.

| Input | Type | Required | Description |
|-------|------|----------|-------------|
| `warnings_only` | boolean | No | Only return the warnings. Default: `false` |

```json
{"warnings_only": true}
```

> "The plugin does not work - what is wrong?" · "What is the URL of the login endpoint?"

#### `security-report`

Score (0-100), grade (A-F) and findings, most severe first, each with the settings paths to change. Covers open registration and deletion, administrator roles, weak or missing signing keys, long JWT lifetimes, unrestricted autologin, open redirects, JWTs in URLs, CORS and logging.

| Input | Type | Required | Description |
|-------|------|----------|-------------|
| `min_severity` | string | No | `info` (default), `warning` or `critical`: only list findings at least this severe. The score always counts every finding |

```json
{"min_severity": "warning"}
```

> "Is my setup secure?" · "Harden the plugin configuration."

#### `explain-error`

Explains an `error_code` from a REST response (for example `{"success":false,"data":{"message":"...","error_code":48}}`) or from a failed settings save (codes of 1000 and above, for example `2005`): the constant, HTTP status, likely causes and the related settings paths. Administrators also get the current values of those settings (secrets masked). Any logged-in user can run it.

| Input | Type | Required | Description |
|-------|------|----------|-------------|
| `error_code` | integer | Yes | The error code |

```json
{"error_code": 48}
```

> "My app gets error_code 48, why?"

#### `inspect-jwt`

Decodes a JWT and checks it like the endpoints do: matching JWT rule, expected algorithm, signature, expiry, two-factor interim tokens, the claim used to find the user, whether that user exists and whether the token was revoked. The signing key is never returned. Google and Auth0 tokens are only decoded (the provider verifies them at request time).

| Input | Type | Required | Description |
|-------|------|----------|-------------|
| `jwt` | string | Yes | The token |

```json
{"jwt": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."}
```

> "Why is this token rejected? eyJ0eXAi..."

Only pass tokens shared on purpose: anyone holding a valid JWT can act as its user.

### API keys

#### `list-api-keys`

API keys, newest first: id, name, masked prefix, permissions, owner, expiry, last use and revocation date. Raw keys are never returned.

| Input | Type | Required | Description |
|-------|------|----------|-------------|
| `page` | integer | No | Page number. Default: 1 |
| `per_page` | integer | No | 1-100. Default: 20 |

```json
{"page": 1, "per_page": 50}
```

> "Which API keys have not been used in the last month?"

#### `create-api-key`

Needs Write access and **Allow creating API keys**. Creates a key owned by the user the agent acts as. The raw key is returned **once**, in the `key` field.

| Input | Type | Required | Description |
|-------|------|----------|-------------|
| `name` | string | Yes | A name to recognize the key |
| `permissions` | string[] | Yes | One or more of `read`, `create`, `update`, `delete` |
| `expires_at` | string | No | `Y-m-d H:i:s`, UTC. Omit for a key that never expires |

```json
{"name": "Reporting dashboard", "permissions": ["read"], "expires_at": "2027-01-31 23:59:59"}
```

> "Create a read-only API key for the reporting dashboard that expires at the end of January."

#### `revoke-api-key`

Revokes a key by id (from `list-api-keys`). Requests with it are rejected at once. This can not be undone.

| Input | Type | Required | Description |
|-------|------|----------|-------------|
| `id` | integer | Yes | The key id |

```json
{"id": 3}
```

> "Revoke the CI deploy API key."

### Audit log

#### `get-audit-log`

Audit log entries, newest first. Only events enabled in **Audit Log** are recorded.

| Input | Type | Required | Description |
|-------|------|----------|-------------|
| `event_type` | string | No | One event, for example `auth.login.failed`, `api_key.used`, `ai.ability.success` or `cli.command.failed` |
| `status` | string | No | `success` or `failure` |
| `user_id` | integer | No | Entries of one user |
| `user_email` | string | No | Entries of one email |
| `date_from` | string | No | `YYYY-MM-DD`, inclusive |
| `date_to` | string | No | `YYYY-MM-DD`, inclusive |
| `page` | integer | No | Default: 1 |
| `per_page` | integer | No | 1-100. Default: 20 |

```json
{"event_type": "auth.login.failed", "date_from": "2026-11-01", "date_to": "2026-11-27", "per_page": 50}
```

> "Show the failed logins of jane@example.com this week."

#### `analyze-audit-log`

Summary of the last N hours: counts and failure rates per event, a timeline, the IPs, users and API keys with the most failures, the most common failure messages, and findings such as brute force, credential stuffing or failure spikes. Also reports events that are not recorded, so an empty result is not mistaken for "all clear".

| Input | Type | Required | Description |
|-------|------|----------|-------------|
| `hours` | integer | No | 1-2160 (90 days). Default: 24 |
| `limit` | integer | No | Rows per top list, 1-50. Default: 10 |

```json
{"hours": 168, "limit": 5}
```

> "Is anyone attacking my logins?" · "Why do logins fail since this morning?"

### Webhooks

Events: `login`, `register`, `auth`, `delete_user`, `reset_password_request`, `reset_password`.

#### `list-webhooks`

The webhooks with their index, URL, method, events, headers (values masked), payload template and timeout, plus whether webhooks and webhook logs are enabled.

| Input | Type | Required | Description |
|-------|------|----------|-------------|
| `event` | string | No | Only webhooks of this event |

```json
{"event": "login"}
```

> "Which webhooks run on login?"

#### `get-webhook-logs`

Recent webhook calls, newest first: URL (query values masked), event, method, HTTP status (null when the request failed) and the start of the response body. Calls are only logged while **Webhook logs** are on (`webhooks.logs.enabled`).

| Input | Type | Required | Description |
|-------|------|----------|-------------|
| `event` | string | No | Only calls of this event |
| `status` | string | No | `success` or `failure` |
| `date_from` | string | No | `YYYY-MM-DD` |
| `date_to` | string | No | `YYYY-MM-DD` |
| `page` | integer | No | Default: 1 |
| `per_page` | integer | No | 1-100. Default: 20 |

```json
{"status": "failure", "date_from": "2026-11-20"}
```

> "Why did the register webhook fail yesterday?"

#### `add-webhook`

Needs Write access and **Allow adding webhooks**. Adds a webhook. Only HTTPS URLs are accepted. Webhooks only fire while `webhooks.enabled` is on.

| Input | Type | Required | Description |
|-------|------|----------|-------------|
| `url` | string | Yes | HTTPS URL |
| `events` | string[] | Yes | One or more events |
| `method` | string | No | `GET`, `POST` (default), `PUT`, `PATCH` or `DELETE` |
| `headers` | object[] | No | `[{"key": "...", "value": "..."}]` |
| `payload_template` | string | No | Body to send, with `{{event}}` and the event's payload fields (for example `{{user_id}}`, `{{user_email}}`). Default: the payload as JSON. Not used by `GET` webhooks, which send the fields as query parameters |
| `timeout` | integer | No | Seconds, 1-30. Default: 30 |
| `enabled` | boolean | No | Default: `true` |

```json
{
  "url": "https://hooks.example.com/sjl",
  "events": ["login", "register"],
  "headers": [{"key": "X-Signature", "value": "my-shared-secret"}],
  "payload_template": "{\"event\":\"{{event}}\",\"user\":{{user_id}}}",
  "timeout": 10
}
```

> "Send new registrations to https://hooks.example.com/sjl."

#### `remove-webhook`

Removes a webhook. Both `index` and `url` (from `list-webhooks`) must match, so a stale index never removes the wrong webhook. The following webhooks move down by one index.

| Input | Type | Required | Description |
|-------|------|----------|-------------|
| `index` | integer | Yes | The webhook index |
| `url` | string | Yes | Its URL, as returned by `list-webhooks` |

```json
{"index": 0, "url": "https://hooks.example.com/sjl"}
```

> "Remove the webhook to hooks.example.com."

The same access level, AI permissions and secret masking apply on every path (MCP, REST and PHP).

## Troubleshooting with AI

Paste an error into your AI client, for example `{"success":false,"data":{"message":"Wrong user credentials.","error_code":48}}`, and ask it to fix the problem. The agent can:

1. Run `simple-jwt-login/diagnose` to see endpoint URLs, what is enabled and the setup warnings.
2. Run `simple-jwt-login/explain-error` to map the `error_code` to its cause and the current values of the settings behind it.
3. Run `simple-jwt-login/inspect-jwt` on a token the client sent, to find out whether the format, signature, expiry, JWT rule or user lookup fails.
4. Fix the cause with `simple-jwt-login/update-settings`, which validates the change like an admin-page save.

You can also ask "is anyone attacking my logins?" or "why do logins fail since this morning?". The agent runs `simple-jwt-login/analyze-audit-log` for the overview and `simple-jwt-login/get-audit-log` to look at single entries. Analysis needs the audit log to be enabled (**Simple JWT Login > Audit Log**) with the relevant events selected.

Ask "is my setup secure?" and the agent runs `simple-jwt-login/security-report`.

## Security

* Only administrators (`manage_options`) can run abilities, except `explain-error`, which any logged-in user can run (without the current setting values). Logged-out visitors cannot run any ability. The access level and the user's capability are checked again on every call.
* Secrets can never be written by an AI agent, and are masked unless **Allow viewing secrets** is on: JWT signing keys, OAuth client secrets, the refresh token key, auth code values, webhook header values and webhook URL query values. Audit log and webhook log output is always masked. `inspect-jwt` only reports whether a signature is valid.
* The AI access level and AI permissions can only be changed from the admin page, and are checked again on every call. Webhooks are changed with the dedicated webhook abilities.
* Every change goes through the same validation as a save from the admin page; nothing is saved when it fails.
* Changes made by AI agents are recorded in the audit log (`AI Ability Executed` / `AI Ability Failed` events) when audit logging is enabled.
* Audit log entries, webhook responses and JWT payloads come from outside users. An agent should treat them as data, not as instructions.
