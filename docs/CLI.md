# Simple JWT Login - WP-CLI

[< Back to the README](../README.md) · [AI Abilities guide](AI-ABILITIES.md)

Set up the plugin, issue and revoke JWTs, manage API keys, auth codes and settings, and troubleshoot from the command line - handy for scripts, CI, deployments and local development. The commands are part of the plugin: no add-on to install.

```bash
wp jwt --help
wp jwt config --help
```

> [!NOTE]
> The commands are **on by default**. Turn them off in **Simple JWT Login > General > CLI**.
> Using the separate **Simple-JWT-Login CLI** plugin? It is no longer needed: while it is active, the built-in commands stay off (and the CLI card shows a warning). Deactivate and delete it.

| Command | What it does |
|---------|--------------|
| [`wp jwt setup`](#wp-jwt-setup) | Generate a signing key when there is none and enable `/auth` |
| [`wp jwt login`](#wp-jwt-login) | Authenticate a user and print a JWT |
| [`wp jwt generate`](#wp-jwt-generate) | Issue a JWT for a user, without their password |
| [`wp jwt refresh`](#wp-jwt-refresh) | Exchange a refresh token for a new JWT |
| [`wp jwt decode`](#wp-jwt-decode) | Show the claims of a JWT (signature not verified) |
| [`wp jwt validate`](#wp-jwt-validate) | Verify signature, expiry, user and revocation |
| [`wp jwt revoke`](#wp-jwt-revoke) | Block a JWT and delete the user's refresh tokens |
| [`wp jwt revoke-all`](#wp-jwt-revoke-all) | Block every JWT and refresh token of a user |
| [`wp jwt key generate` / `rotate`](#wp-jwt-key) | Print a new signing key, or replace the stored one |
| [`wp jwt api-key create` / `list` / `revoke`](#wp-jwt-api-key) | Manage API keys |
| [`wp jwt auth-code list` / `add` / `remove`](#wp-jwt-auth-code) | Manage auth codes |
| [`wp jwt config get` / `set` / `list` / `export` / `import`](#settings) | Read and change the settings |
| [`wp jwt doctor`](#wp-jwt-doctor) | Versions, REST URLs, endpoints and configuration warnings |
| [`wp jwt security-report`](#wp-jwt-security-report) | Security score (A-F) and findings |
| [`wp jwt explain-error`](#wp-jwt-explain-error) | What an error code means and which settings to check |
| [`wp jwt log list` / `analyze`](#wp-jwt-log) | Read and summarize the audit log |
| [`wp jwt webhook list` / `test` / `logs`](#wp-jwt-webhook) | List webhooks, call one now, read the call log |
| [`wp jwt cleanup`](#wp-jwt-cleanup) | Run the daily cleanup now |

Single results accept `--format=text` (default) or `--format=json`. Lists accept `--format=table` (default), `csv`, `json`, `yaml` or `count`, and `--fields=a,b`. Lists are paged, and `count` counts the rows of the current page (at most `--per-page`), not every row. A `<user>` argument is a user ID, login or email, like in `wp user get`. Commands that change something ask for confirmation; `--yes` skips it.

> [!NOTE]
> With the audit log on, commands that change something are recorded as `cli.command.success` / `cli.command.failed` (enable the events in **Audit Logs**), with the WordPress user of `--user=<user>`. A command that turns the audit log off is still recorded.

## Setup

### `wp jwt setup`

Get a fresh site ready to issue JWTs in one command: it generates a signing key when the configured algorithm has none, enables **Authentication**, and prints the `/auth` URL with a sample `curl`.

```
wp jwt setup [--algorithm=<algorithm>] [--force]
```

| Option | Required | Description |
|--------|----------|-------------|
| `--algorithm=<algorithm>` | No | `HS256`, `HS384`, `HS512`, `RS256`, `RS384`, `RS512`, or `random` to pick one. Default: the configured algorithm. A different algorithm always gets a new key |
| `--force` | No | Generate a new key even when there is one |

A replaced key keeps working for the tokens it signed until they expire, as with `wp jwt key rotate`. Keys defined in `wp-config.php` are kept, and the algorithm can not be changed from the command line in that case.

```bash
wp jwt setup                               # keep the key if there is one, enable /auth
wp jwt setup --algorithm=RS256             # switch to RS256 with a new RSA key pair
wp jwt setup --force                       # new key, same algorithm
```

## Tokens

### `wp jwt login`

Authenticate a WordPress user and print a JWT.

> [!NOTE]
> Authentication must be enabled: **Authentication > Allow Authentication**, or `wp jwt config set authorization.enabled 1`.

```
wp jwt login [--username=<username>] [--email=<email>] [--login=<login>] [--password=<password>] [--format=<format>]
```

| Option | Required | Description |
|--------|----------|-------------|
| `--username=<username>` | One of the three identifiers | WordPress username |
| `--email=<email>` | One of the three identifiers | WordPress user email address |
| `--login=<login>` | One of the three identifiers | Username or email - tried as username first, then email |
| `--password=<password>` | No | WordPress user password. Leave it out to type it (hidden) or pipe it on STDIN, so it is not saved in the shell history |
| `--format=<format>` | No | `text` (default) or `json` |

When several identifiers are given: `--username` > `--email` > `--login`. When **Refresh Token** is enabled, a refresh token is stored like on `/auth`. Users with two-factor authentication can not get a JWT from the command line.

```bash
wp jwt login --username=admin                      # asks for the password
wp jwt login --email=admin@example.com --format=json < password.txt
# {"jwt":"eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."}

# Use the token right away
TOKEN=$(wp jwt login --username=admin --password=secret)
curl -H "Authorization: Bearer $TOKEN" https://example.com/wp-json/wp/v2/users/me
```

### `wp jwt generate`

Issue a JWT for a user without their password: same claims and signing key as `/auth`, without two-factor authentication. Prints a refresh token too when **Refresh Token** is enabled.

```
wp jwt generate <user> [--ttl=<minutes>] [--format=<format>]
```

| Option | Required | Description |
|--------|----------|-------------|
| `<user>` | Yes | User ID, login or email |
| `--ttl=<minutes>` | No | Expiration in minutes, at least 1. Default: **Authentication > JWT TTL** |
| `--format=<format>` | No | `text` (default) or `json`. In `text`, only the JWT is printed when **Refresh Token** is off; when it is on, `jwt` and `refresh_token` lines are printed |

```bash
# Short-lived token for a script (works whether refresh tokens are on or off)
TOKEN=$(wp jwt generate admin --ttl=10 --format=json | jq -r '.jwt')
wp jwt generate 42 --format=json
# {"jwt":"eyJ0eXAiOiJKV1Qi...","refresh_token":"3f2a..."}
```

> [!WARNING]
> Anyone who can run WP-CLI can get a token for any user, administrators included. Keep shell access restricted, or turn the commands off in **General > CLI**.

### `wp jwt refresh`

Exchange a refresh token for a new JWT and refresh token, like `/auth/refresh`. The endpoint checks apply: **Refresh Token** must be enabled, **Allowed IPs** must be empty (the command line has no client IP), and the auth code is needed when the endpoint requires one. The old refresh token stops working.

```
wp jwt refresh <refresh-token> [--auth-code=<code>] [--format=<format>]
```

| Option | Required | Description |
|--------|----------|-------------|
| `<refresh-token>` | Yes | The refresh token from `/auth`, `wp jwt login` or `wp jwt generate` |
| `--auth-code=<code>` | When the refresh endpoint requires an auth code | The auth code |
| `--format=<format>` | No | `text` (default) or `json` |

```bash
wp jwt refresh 3f2a...c9
# jwt                  eyJ0eXAiOiJKV1Qi...
# refresh_token        8b1d...e4
wp jwt refresh 3f2a...c9 --auth-code=MY_CODE --format=json | jq -r '.jwt'
```

### `wp jwt decode`

Show the payload of a JWT. The signature is **not** verified - use `wp jwt validate` for that.

```
wp jwt decode <token> [--format=<format>]
```

| Option | Required | Description |
|--------|----------|-------------|
| `<token>` | Yes | The JWT |
| `--format=<format>` | No | `text` (default, one `claim value` line per claim) or `json` |

```bash
wp jwt decode eyJhbGciOiJIUzI1NiJ9.eyJpZCI6MSwiZXhwIjoxOTk5OTk5fQ.sig
# id                   1
# exp                  1999999

# Read a single claim with jq
wp jwt decode "$TOKEN" --format=json | jq '.exp'
```

### `wp jwt validate`

Validate a JWT with the same checks as the plugin endpoints: JWT rule, signature, expiry, audience, user and revocation.

```
wp jwt validate <token> [--format=<format>]
```

| Option | Required | Description |
|--------|----------|-------------|
| `<token>` | Yes | The JWT |
| `--format=<format>` | No | `text` (default) or `json` (`{"valid":true,"message":"..."}`) |

The command exits with `1` when the token is not valid, so it can be used in `if` and CI steps.

```bash
wp jwt validate "$TOKEN"
# valid                yes
# message              Token is valid.

if wp jwt validate "$TOKEN" > /dev/null; then
  echo "Token OK"
fi
```

### `wp jwt revoke`

Revoke a JWT so it can no longer be used. The signature is verified first, and the user's refresh tokens are deleted too, like the revoke endpoint does.

```
wp jwt revoke <token> [--format=<format>]
```

| Option | Required | Description |
|--------|----------|-------------|
| `<token>` | Yes | The JWT to revoke |
| `--format=<format>` | No | `text` (default) or `json` |

```bash
wp jwt revoke "$TOKEN"
# success              yes
# message              Token has been revoked.
```

### `wp jwt revoke-all`

Block every JWT and refresh token of a user issued until now - for example after a leaked token or a lost device. Works whether or not **Revoke tokens on password change** is on. Tokens issued afterwards work (from the next second on).

```
wp jwt revoke-all <user> [--yes]
```

| Option | Required | Description |
|--------|----------|-------------|
| `<user>` | Yes | User ID, login or email |
| `--yes` | No | Do not ask for confirmation |

```bash
wp jwt revoke-all jane@example.com          # asks "Revoke every token of jane?"
wp jwt revoke-all 42 --yes                  # scripts
```

### `wp jwt key`

```
wp jwt key generate [--algorithm=<algorithm>] [--format=<format>]
wp jwt key rotate [--algorithm=<algorithm>] [--immediate] [--show] [--yes]
```

| Option | Command | Description |
|--------|---------|-------------|
| `--algorithm=<algorithm>` | both | `HS256`, `HS384`, `HS512`, `RS256`, `RS384`, `RS512`, or `random` to pick one (the chosen one is printed). Default: the configured algorithm |
| `--format=<format>` | `generate` | `text` (default: the key, or the private then the public PEM) or `json` (`algorithm` + the key fields) |
| `--immediate` | `rotate` | Do not keep the old key: every token signed with it, or with a key kept by an earlier rotation, stops working now |
| `--show` | `rotate` | Print the new key. Without it the key is only saved |
| `--yes` | `rotate` | Do not ask for confirmation |

`generate` prints a random key (HS256/384/512: as many random bytes as the hash size, hex encoded) or an RSA key pair (RS256: 2048, RS384: 3072, RS512: 4096 bits) without saving it. `rotate` stores a new key (and algorithm, with `--algorithm`; `--algorithm=random` picks one of the six and prints which) without breaking issued tokens: new tokens are signed with the new key, and the old key moves to a **JWT Rule** (payload `iat` *less than* the rotation time), so tokens issued before keep working until they expire. The daily cleanup (or `wp jwt cleanup`, or the next rotation) removes that rule once the JWT TTL plus 60 seconds has passed. Refresh tokens keep working too: when no **Refresh Token** key is set, the old key becomes it. Your own JWT rules are not changed, and are checked before the rotation rules. The new key is printed only with `--show`. A key defined in `wp-config.php` must be changed there.

> [!WARNING]
> Until its rule is removed, the old key can still sign valid tokens. If a key leaked, use `--immediate`: every token signed with it (or with a key kept by an earlier rotation) stops working at once.
>
> Tokens that live longer than the JWT TTL (`wp jwt generate --ttl`, or a payload without `exp`) stop working when the rule is removed.

```bash
wp jwt key generate --algorithm=HS512                  # print a key to paste somewhere else
wp jwt key generate --algorithm=RS256 --format=json    # RSA key pair as JSON
wp jwt key rotate --yes                                # scheduled rotation, issued tokens keep working
wp jwt key rotate --algorithm=RS256 --show             # switch to RS256 and print the key pair
wp jwt key rotate --immediate --yes                    # the key leaked: invalidate every issued token now
```

### `wp jwt api-key`

```
wp jwt api-key create <owner> --name=<name> --permissions=<list> [--expires-at=<date>] [--format=<format>]
wp jwt api-key list [--page=<n>] [--per-page=<n>] [--fields=<fields>] [--format=<format>]
wp jwt api-key revoke <id>
```

| Option | Command | Required | Description |
|--------|---------|----------|-------------|
| `<owner>` | `create` | Yes | User ID, login or email of the key owner. The owner's capabilities apply, as in wp-admin: a user can only grant the permissions they have |
| `--name=<name>` | `create` | Yes | A name to recognize the key, for example `CI deploy` |
| `--permissions=<list>` | `create` | Yes | Comma separated: `read`, `create`, `update`, `delete` |
| `--expires-at=<date>` | `create` | No | Expiration date, `Y-m-d H:i:s` in UTC. Default: never |
| `--format=<format>` | `create` | No | `text` (default) or `json` |
| `--page=<n>` | `list` | No | Page number. Default: 1 |
| `--per-page=<n>` | `list` | No | Keys per page. Default: 100 |
| `--fields=<fields>` | `list` | No | Default: `id,user_id,name,key_prefix,permissions,expires_at,last_used_at,revoked_at` |
| `--format=<format>` | `list` | No | `table` (default), `csv`, `json`, `yaml` or `count` |
| `<id>` | `revoke` | Yes | The key ID, from `list`. A revoked key stays in the list with its `revoked_at` date |

The key is printed once, at creation - copy it then; `list` only shows its prefix.

```bash
wp jwt api-key create admin --name="CI deploy" --permissions=read --format=json
wp jwt api-key create 42 --name="Mobile app" --permissions=read,create,update --expires-at="2027-01-31 23:59:59"
wp jwt api-key list --fields=id,name,key_prefix,revoked_at
wp jwt api-key revoke 3
```

### `wp jwt auth-code`

```
wp jwt auth-code list [--show] [--fields=<fields>] [--format=<format>]
wp jwt auth-code add [<code>] [--role=<role>] [--expires=<date>]
wp jwt auth-code remove <index> [--yes]
```

| Option | Command | Required | Description |
|--------|---------|----------|-------------|
| `--show` | `list` | No | Show the codes. Without it they are masked |
| `--fields=<fields>` | `list` | No | Default: `index,code,role,expiration_date` |
| `--format=<format>` | `list` | No | `table` (default), `csv`, `json`, `yaml` or `count` |
| `<code>` | `add` | No | The code. Leave it out to generate a random one (printed once) |
| `--role=<role>` | `add` | No | Role given to users who register with this code. Default: the **Register** setting |
| `--expires=<date>` | `add` | No | Expiration date, for example `2027-01-31 23:59:59`. Default: never |
| `<index>` | `remove` | Yes | The code's `index`, from `list` |
| `--yes` | `remove` | No | Do not ask for confirmation |

Codes are hidden in `list` unless `--show` is passed. `add` without a code generates and prints a random one.

```bash
wp jwt auth-code add --role=subscriber                       # random code, printed once
wp jwt auth-code add MY_CODE --expires="2027-01-31 23:59:59"
wp jwt auth-code list --show
wp jwt auth-code remove 0 --yes
```

> [!TIP]
> `validate` and `revoke` need the signing key and algorithm (**General**), and the **Login > Login by** setting must match a claim in the token.

## Settings

Settings use dot notation for nested keys, for example `general.jwt_algorithm` or `cors.enabled`. `wp jwt config list` shows every key.

<details>
<summary><b>Old setting keys</b> - keys from Simple JWT Login 3.x are renamed</summary>

A 3.x key fails with an error that names the new key. Importing a full 3.x export file converts it automatically.

| 3.x | 4.x |
|-----|-----|
| `allow_authentication` | `authorization.enabled` |
| `jwt_auth_ttl` | `authorization.ttl` |
| `jwt_payload` | `authorization.jwt_payload` |
| `jwt_algorithm` | `general.jwt_algorithm` |
| `decryption_key` | `general.decryption_key` |
| `jwt_login_by` | `login.login_by` |
| `jwt_login_by_parameter` | `login.login_by_parameter` |

</details>

### `wp jwt config get`

Print one setting. An array setting is printed as JSON.

```
wp jwt config get <key> [--format=<format>]
```

| Option | Required | Description |
|--------|----------|-------------|
| `<key>` | Yes | Setting key in dot notation, for example `cors.enabled` |
| `--format=<format>` | No | `text` (default) or `json` (the value JSON encoded, for example `"HS256"` or `true`) |

```bash
wp jwt config get authorization.enabled
# 1
wp jwt config get authorization.jwt_payload --format=json
# ["iat","exp","id"]
```

### `wp jwt config set`

Change a setting. The new settings are validated like a save from the admin page; nothing is saved when validation fails.

```
wp jwt config set <key> <value> [--type=<type>] [--force]
```

| Option | Required | Description |
|--------|----------|-------------|
| `<key>` | Yes | Setting key in dot notation. It must be inside a section (`general.jwt_algorithm`, not `jwt_algorithm`) |
| `<value>` | Yes | The new value. Booleans accept `1`, `true`, `yes`, `on` (anything else is false) |
| `--type=<type>` | No | `auto` (default), `string`, `int`, `bool` or `json` |
| `--force` | No | Skip the validation and save anyway - only to repair settings that can not be fixed otherwise |

With `--type=auto` the type follows the stored value: a boolean stays a boolean, an integer stays an integer, and an array expects a JSON string. A key without a stored value is saved as a string.

```bash
wp jwt config set authorization.enabled 1
wp jwt config set general.jwt_algorithm RS256
wp jwt config set authorization.ttl 120 --type=int
wp jwt config set general.decryption_key "my-super-secret"
wp jwt config set authorization.jwt_payload '["iat","exp","id","email"]' --type=json
```

### `wp jwt config list`

```
wp jwt config list [--format=<format>]
```

| Option | Required | Description |
|--------|----------|-------------|
| `--format=<format>` | No | `text` (default): one `key  value` line per setting, lists as JSON. `json`: the full settings object |

> [!WARNING]
> `list` and `get` print secrets such as the signing key in plain text.

```bash
wp jwt config list
# authorization.enabled             1
# general.jwt_algorithm             HS256
# ...
```

### `wp jwt config export`

Print every setting as JSON, or save it to a file, to back it up or copy it to another site with `import`.

```
wp jwt config export [--file=<path>]
```

| Option | Required | Description |
|--------|----------|-------------|
| `--file=<path>` | No | Write the JSON to this file instead of printing it. The file is readable by its owner only (`0600`) |

> [!WARNING]
> The export contains secrets such as the signing key. Keep the file safe.

```bash
wp jwt config export --file=jwt-config.json
wp jwt config export | jq '.general.jwt_algorithm'
```

### `wp jwt config import`

Import settings from a JSON file made by `wp jwt config export`. By default the **whole** settings object is replaced; `--merge` only changes the keys in the file. The command prints a diff (`[+]` added, `[~]` changed, `[-]` removed; secret values are masked) and asks for confirmation before saving.

```
wp jwt config import <file> [--merge] [--dry-run] [--yes] [--force]
```

| Option | Required | Description |
|--------|----------|-------------|
| `<file>` | Yes | Path to the JSON file |
| `--merge` | No | Merge into the current settings instead of replacing them. Lists (for example `authorization.jwt_payload`) are replaced |
| `--dry-run` | No | Show what would change without saving |
| `--yes` | No | Skip the confirmation prompt (scripts and CI) |
| `--force` | No | Skip the validation and save anyway |

```bash
# Copy the configuration from one site to another
wp jwt config export --file=jwt-config.json             # source site
wp jwt config import jwt-config.json --dry-run          # target site: preview
wp jwt config import jwt-config.json --yes              # target site: apply

# Change only a few settings from a partial file
echo '{"cors":{"enabled":1,"allow_origin_enabled":true,"allow_origin":"https://app.example.com"}}' > cors.json
wp jwt config import cors.json --merge --yes
```

## Diagnostics, logs and webhooks

### `wp jwt doctor`

Check the installation: plugin, WordPress and PHP versions, the REST URLs, each endpoint (enabled, auth code required), the JWT settings and configuration warnings. Run it first when something does not work.

```
wp jwt doctor [--format=<format>]
```

| Option | Required | Description |
|--------|----------|-------------|
| `--format=<format>` | No | `text` (default: summary plus endpoint and warning tables) or `json` |

```bash
wp jwt doctor
wp jwt doctor --format=json | jq '.warnings'
```

### `wp jwt security-report`

Score the configuration from 0 to 100 (grade A-F) and list what to improve, for example a short signing key or a missing auth code.

```
wp jwt security-report [--min-severity=<severity>] [--fields=<fields>] [--format=<format>]
```

| Option | Required | Description |
|--------|----------|-------------|
| `--min-severity=<severity>` | No | Least severe findings to list: `info` (default), `warning` or `critical` |
| `--fields=<fields>` | No | Finding fields in the text table. Default: `severity,code,message,settings` |
| `--format=<format>` | No | `text` (default) or `json` |

```bash
wp jwt security-report
# Score: 80/100 (grade B)
wp jwt security-report --min-severity=warning --format=json
wp jwt security-report --fields=severity,message
```

### `wp jwt explain-error`

Explain an error code from an API response (`"error_code": 48`) or a settings save (codes of 1000 and above, for example `2005`): what it means, the HTTP status and the settings to check. For settings codes, `constant` names the settings section (for example `PREFIX_GENERAL`).

The current values of the related settings (`settings`) are only shown to administrators: pass `--user=<admin>`, otherwise the list is empty.

```
wp jwt explain-error <code> [--format=<format>]
```

| Option | Required | Description |
|--------|----------|-------------|
| `<code>` | Yes | The error code |
| `--format=<format>` | No | `text` (default) or `json` |

```bash
wp jwt explain-error 48 --user=admin
# constant             ERR_AUTHENTICATION_WRONG_CREDENTIALS
# http_status          401
# explanation          Wrong username/email or password. ...
```

### `wp jwt log`

```
wp jwt log list [--event-type=<event>] [--status=<success|failure>] [--user-id=<id>] [--page=<n>] [--per-page=<n>] [--fields=<fields>] [--format=<format>]
wp jwt log analyze [--hours=<hours>] [--format=<format>]
```

Read the audit log (**Audit Logs** must be enabled for new entries to be recorded; older entries can still be read when it is off).

| Option | Command | Description |
|--------|---------|-------------|
| `--event-type=<event>` | `list` | Only one event, for example `auth.login.failed` or `cli.command.success` |
| `--status=<status>` | `list` | `success` or `failure` |
| `--user-id=<id>` | `list` | Only entries of this user ID |
| `--page=<n>` | `list` | Page number. Default: 1 |
| `--per-page=<n>` | `list` | Entries per page, up to 100. Default: 20 |
| `--fields=<fields>` | `list` | Default: `id,created_at,event_type,status,user_id,ip_address,message` |
| `--format=<format>` | `list` | `table` (default), `csv`, `json`, `yaml` or `count` |
| `--hours=<hours>` | `analyze` | Time window in hours. Default: 24 |
| `--format=<format>` | `analyze` | `text` (default) or `json` |

Entries are listed newest first. `analyze` shows failure rates per event, the top failing IPs, users and API keys, and findings (for example brute force).

```bash
wp jwt log list --status=failure --per-page=50
wp jwt log list --event-type=auth.login.failed --user-id=42 --format=csv > failed-logins.csv
wp jwt log list --event-type=cli.command.success --fields=created_at,user_id,message
wp jwt log list --status=failure --format=count
wp jwt log analyze --hours=168                       # last 7 days
```

### `wp jwt webhook`

```
wp jwt webhook list [--event=<event>] [--fields=<fields>] [--format=<format>]
wp jwt webhook test <index> [--event=<event>]
wp jwt webhook logs [--event=<event>] [--page=<n>] [--per-page=<n>] [--fields=<fields>] [--format=<format>]
```

| Option | Command | Description |
|--------|---------|-------------|
| `--event=<event>` | `list` | Only webhooks of this event, for example `login` |
| `--fields=<fields>` | `list` | Default: `index,enabled,method,url,events`. Header values are always hidden |
| `--format=<format>` | `list`, `logs` | `table` (default), `csv`, `json`, `yaml` or `count` |
| `<index>` | `test` | The webhook `index`, from `list` |
| `--event=<event>` | `test` | Event sent in the test payload. Default: the webhook's first event |
| `--event=<event>` | `logs` | Only calls of this event |
| `--page=<n>` | `logs` | Page number. Default: 1 |
| `--per-page=<n>` | `logs` | Entries per page. Default: 20 |
| `--fields=<fields>` | `logs` | Default: `id,created_at,event,method,url,status_code` |

`test` calls the webhook now with a test payload (even when the webhook or webhooks are disabled) and prints the response; it fails when the answer is not 2xx. The call is logged when **Webhook logs** are on. `logs` lists recorded calls, newest first, and needs **Webhook logs** enabled. The test payload is `{"user_id": 0, "user_email": "", "test": true, "event": "<event>"}`, rendered through the webhook's payload template; a `GET` webhook sends these fields as query parameters instead (the template is not used).

```bash
wp jwt webhook list --event=login
wp jwt webhook test 0                        # check the URL and headers before going live
wp jwt webhook test 0 --event=register
wp jwt webhook logs --event=login --per-page=50
```

### `wp jwt cleanup`

Run the daily cleanup now, instead of waiting for WP-Cron: expired refresh and revoked tokens, log entries older than their retention period, and signing keys kept by `wp jwt key rotate` once their tokens expired. It takes no options.

```bash
wp jwt cleanup
```

<details>
<summary><b>Troubleshooting</b> - common CLI errors and how to fix them</summary>

| Error | Cause | Fix |
|-------|-------|-----|
| `'jwt' is not a registered wp command` | The CLI is turned off, or the old Simple-JWT-Login CLI plugin is active | Turn on **General > CLI**, or deactivate the old plugin |
| `Authentication is not enabled.` | The Authentication section is off | `wp jwt config set authorization.enabled 1` |
| `Wrong user credentials.` | Wrong username, email or password | Check the credentials |
| `Please provide --username, --email, or --login` | No identifier was given | Add `--username`, `--email` or `--login` |
| `Please provide --password` | No password was given or typed | Add `--password`, type it at the prompt, or pipe it on STDIN |
| `User not found: <user>` | No user has this ID, login or email | Check with `wp user get <user>` |
| `This user must complete two-factor authentication.` | The user has 2FA enabled | A JWT can not be created from the command line for this user |
| `Could not decode token. ...` | The token does not have three dot-separated parts | Pass the full token |
| `valid: no` (exit code 1) | `wp jwt validate` rejected the token | The `message` says why (expired, bad signature, ...) |
| `The signing key is defined in wp-config.php. ...` | `key rotate` with the key in code | Change the constants in `wp-config.php` |
| `You are not allowed to Authenticate from this IP` | `refresh` with **Allowed IPs** set | The CLI has no client IP: refresh over HTTP |
| `Token has already been revoked.` | The token was revoked before | Nothing to do |
| `User not found.` | No WordPress user matches the token | Make sure the token was issued for an existing user |
| `Unable to find user <claim> property in JWT.` | The **Login by** claim is not in the payload | Issue a new token, or change **Login > Login by** |
| `Setting <key> is now <new key>.` | A 3.x key was used | Use the new key |
| `Validation failed: ...` | The new settings are not valid | Fix the value, or add `--force` |
| `Setting "<key>" not found.` | The key has no stored value | The plugin default is used; `set` stores a value |
| `The current value is an array. ...` | An array setting was set without JSON | Add `--type=json` and pass a JSON array or object |
| `Invalid JSON value: ...` / `Invalid JSON in <file>: ...` | Malformed JSON | Check it with `jq .` first |
| `Cannot read file: <path>` | The file does not exist or is not readable | Check the path and permissions |
| `No changes detected.` | The file matches the stored settings | Nothing to do |

</details>
