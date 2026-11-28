<p align="center">
    <img src="https://ps.w.org/simple-jwt-login/assets/banner-772x250.png?rev=2106097" alt="Simple JWT Login banner"/>
</p>

<h3 align="center">JWT authentication for the WordPress REST API - free, open source, no code required</h3>

<p align="center">
  <a href="https://wordpress.org/plugins/simple-jwt-login/"><img src="https://img.shields.io/wordpress/plugin/v/simple-jwt-login?label=WordPress.org" alt="WordPress.org version"/></a>
  <a href="https://wordpress.org/plugins/simple-jwt-login/#description"><img src="https://img.shields.io/wordpress/plugin/installs/simple-jwt-login" alt="Active installs" /></a>
  <a href="https://wordpress.org/plugins/simple-jwt-login/advanced#plugin-download-stats"><img src="https://img.shields.io/wordpress/plugin/dt/simple-jwt-login" alt="Total downloads" /></a>
  <a href="https://wordpress.org/support/plugin/simple-jwt-login/reviews/"><img src="https://img.shields.io/wordpress/plugin/stars/simple-jwt-login" alt="Rating" /></a>
  <img src="https://img.shields.io/wordpress/plugin/required-php/simple-jwt-login" alt="Required PHP version"/>
  <img src="https://img.shields.io/wordpress/plugin/tested/simple-jwt-login" alt="Tested up to WordPress"/>
</p>

<p align="center">
  <a href="https://github.com/nicumicle/simple-jwt-login/actions/workflows/tests.yml"><img src="https://github.com/nicumicle/simple-jwt-login/actions/workflows/tests.yml/badge.svg" alt="Tests" /></a>
  <a href="https://github.com/nicumicle/simple-jwt-login/actions/workflows/lint.yml"><img src="https://github.com/nicumicle/simple-jwt-login/actions/workflows/lint.yml/badge.svg" alt="Linting and static analysis" /></a>
  <a href="https://github.com/nicumicle/simple-jwt-login/actions/workflows/multiple-php.yml"><img src="https://github.com/nicumicle/simple-jwt-login/actions/workflows/multiple-php.yml/badge.svg" alt="Multiple PHP versions"/></a>
  <a href="https://app.codecov.io/gh/nicumicle/simple-jwt-login"><img src="https://codecov.io/gh/nicumicle/simple-jwt-login/branch/master/graph/badge.svg?token=dVOwuGQoY3" alt="Coverage"/></a>
  <a href="https://github.com/nicumicle/simple-jwt-login/tags"><img src="https://img.shields.io/github/v/tag/nicumicle/simple-jwt-login" alt="Current tag" /></a>
  <a href="https://github.com/nicumicle/simple-jwt-login/blob/master/LICENSE"><img src="https://img.shields.io/github/license/nicumicle/simple-jwt-login" alt="License" /></a>
</p>

<p align="center">
  <a href="https://github.com/nicumicle/simple-jwt-login/graphs/contributors"><img src="https://img.shields.io/github/contributors/nicumicle/simple-jwt-login" alt="Contributors" /></a>
  <a href="https://github.com/nicumicle/simple-jwt-login/commits/master"><img src="https://img.shields.io/github/last-commit/nicumicle/simple-jwt-login" alt="Last commit"/></a>
  <a href="https://github.com/nicumicle/simple-jwt-login/issues"><img src="https://img.shields.io/github/issues-raw/nicumicle/simple-jwt-login" alt="Open issues"/></a>
  <a href="https://github.com/nicumicle/simple-jwt-login/pulls"><img src="https://img.shields.io/github/issues-pr/nicumicle/simple-jwt-login" alt="Open pull requests" /></a>
  <a href="https://docs.github.com/en/pull-requests/collaborating-with-pull-requests/proposing-changes-to-your-work-with-pull-requests/creating-a-pull-request"><img src="https://img.shields.io/badge/PRs-welcome-brightgreen" alt="PRs welcome" /></a>
  <a href="https://twitter.com/simplejwtlogin"><img src="https://img.shields.io/twitter/follow/simplejwtlogin" alt="Follow on X"></a>
</p>

<p align="center">
  <a href="https://simplejwtlogin.com/docs"><b>Documentation</b></a>
  ·
  <a href="https://github.com/nicumicle/simple-jwt-login/issues/new?assignees=&labels=bug&template=bug_report.md&title=bug%3A+">Report a Bug</a>
  ·
  <a href="https://github.com/nicumicle/simple-jwt-login/issues/new?assignees=&labels=feature-request&template=feature_request.md&title=feature%3A+">Request a Feature</a>
  ·
  <a href="https://github.com/nicumicle/simple-jwt-login/discussions">Ask a Question</a>
</p>

---

**Simple JWT Login** is a **free** WordPress plugin that lets mobile apps, headless frontends and other websites log in, register and call the WordPress REST API securely with JSON Web Tokens. Every feature is a toggle in the admin screen: no PHP required.

<h2 align="center">⚡ Try it live - no install, no account</h2>

<p align="center">
  <a href="https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/nicumicle/simple-jwt-login/master/wordpress.org/assets/blueprints/blueprint.json">
    <img src="https://img.shields.io/badge/Launch%20Live%20Demo-WordPress%20Playground-3858e9?style=for-the-badge&logo=wordpress&logoColor=white" alt="Launch the live demo in WordPress Playground" />
  </a>
</p>

<p align="center">
  Spin up a real WordPress site with Simple JWT Login <b>right in your browser</b>, in about 15 seconds.<br/>
  It runs entirely on your machine with <a href="https://wordpress.org/playground/">WordPress Playground</a>, and disappears when you close the tab.
</p>

<table align="center">
  <tr>
    <td>🔐 <b>Ready to use</b></td>
    <td>A signing key is generated, and the <code>/auth</code>, refresh, validate and revoke endpoints are enabled</td>
  </tr>
  <tr>
    <td>👤 <b>Logged in</b></td>
    <td>You land on the plugin dashboard as <code>admin</code> / <code>password</code></td>
  </tr>
  <tr>
    <td>🧪 <b>Try every endpoint</b></td>
    <td>Endpoint tabs have a <b>Try</b> button that sends a real request and shows the response</td>
  </tr>
  <tr>
    <td>🛡️ <b>Explore it all</b></td>
    <td>OAuth, API keys, protected endpoints, webhooks and audit logs - change anything, nothing can break</td>
  </tr>
</table>

## Overview

<p align="center">
    <img src="https://github.com/nicumicle/simple-jwt-login/blob/master/wordpress.org/schema.png?raw=true" alt="Simple JWT Login schema" />
</p>

## Table of contents

<!--ts-->
* [Installation](#bulb-installation)
  * [Install from WordPress.org](#install-from-wordpressorg)
  * [Install from Zip](#install-from-zip)
  * [Set up the Plugin](#set-up-the-plugin)
* [Features](#tada-features)
* [Comparison](#bar_chart-how-simple-jwt-login-compares)
* [WP-CLI](#computer-wp-cli)
* [Integrations](#link-integrations)
  * [Two-Factor Authentication](#two-factor-authentication)
  * [WooCommerce](#woocommerce)
  * [Force Login](#force-login)
  * [AI Abilities (MCP)](#ai-abilities-mcp)
* [Integrate](#electric_plug-integrate)
  * [PHP SDK](#php-sdk)
  * [JavaScript SDK](#javascript-sdk)
  * [Postman](#postman)
* [Documentation](#ledger-documentation)
* [Roadmap](#rocket-roadmap)
* [Version Support Policy](#calendar-version-support-policy)
* [Contribute](#scroll-contribute)
* [Contributors](#trophy-contributors)
* [Sponsors](#blue_heart-sponsors)
* [Copyright](#copyright)
<!--te-->

## :bulb: Installation

### Install from WordPress.org

> [!TIP]
> For production sites, install the plugin from WordPress.org to get the latest stable version and automatic updates.

1. In your WordPress admin, go to **Plugins > Add New**.
2. Search for **Simple JWT Login** and click **Install Now**.
3. Click **Activate**.

### Install from Zip

1. Download [download/simple-jwt-login.zip](https://github.com/nicumicle/simple-jwt-login/blob/master/download/simple-jwt-login.zip).
2. Go to **Plugins > Add New > Upload Plugin** and upload the zip.
3. Activate the plugin.

### Set up the Plugin

After activating the plugin, open **Simple JWT Login** in your WordPress admin menu.

<details>
<summary><b>1. General (required)</b></summary>

- **Route Namespace** - base URL prefix for all plugin REST endpoints. Default: `simple-jwt-login/v1/`. Change only if another plugin conflicts.
- **JWT Verification Rules** - define how incoming JWTs are verified:
  - For symmetric algorithms (HS256, HS384, HS512): enter a **Decryption Key** (any strong secret string).
  - For asymmetric algorithms (RS256, RS384, RS512): enter a **Public Key** and a **Private Key** (PEM format).
  - The **ELSE** row is the required fallback rule applied when no conditional rule matches.
- **JWT Input Sources** - choose where the plugin looks for the JWT in each request. At least one source must be enabled:
  - **Request (URL param)** - for example `?JWT=<token>` (parameter name is configurable, default: `JWT`)
  - **Header** - for example `Authorization: Bearer <token>` (default header name: `Authorization`)
  - **Cookie** - reads the JWT from a cookie (default cookie name: `simple-jwt-login-token`)
  - **Session** - reads the JWT from a PHP session variable
- **CLI** - turns the [`wp jwt` commands](#computer-wp-cli) on or off (on by default).

Click **Save Changes** before moving to the next section.

</details>

<details>
<summary><b>2. Authentication (optional - generate JWTs via REST)</b></summary>

Go to the **Authentication** tab to enable the `/auth` endpoint that issues JWTs:

- Toggle **Allow Authentication** to enable the endpoint.
- Select the **JWT Payload** fields you want included in the token (for example `email`, `id`, `username`).
- Set **JWT TTL** (token lifetime in minutes, default: 60) and **JWT Refresh TTL** (refresh window, default: 20160).
- Optionally enable **Refresh Token**, **Validate Token**, and **Revoke Token** sub-endpoints.

</details>

<details>
<summary><b>3. Login / Autologin (optional - log users in via JWT)</b></summary>

Go to the **Login** tab to enable browser-based autologin using a JWT:

- Toggle **Allow Autologin** to enable the `/autologin` endpoint.
- Set **Login By** to the user attribute the plugin should match (`email`, `WordPress User ID`, or `User Login`).
- Set **JWT Parameter Key** to the JWT payload claim that holds the matching value (for example `email`).
- Choose a **Redirect** destination after a successful login (Dashboard, Homepage, or a custom URL).

</details>

<details>
<summary><b>4. Auth Codes (optional - restrict API access)</b></summary>

Go to the **Auth Codes** tab to add shared secrets that callers must supply alongside the JWT:

- Enter one or more codes, each with an optional **Role** (limits which WordPress role the code grants access to) and an optional **Expiration Date**.
- Back in the Login, Authentication, Register, or Delete User tabs, enable **Require Auth Code** to enforce it for that operation.

</details>

#### Minimum viable setup

For a headless or mobile app that needs to authenticate users and receive JWTs:

1. **General**: set a Decryption Key and make sure at least one JWT Input Source is enabled.
2. **Authentication**: enable **Allow Authentication** and select the payload fields you need.
3. Click **Save Changes**.

Your JWT endpoint is now available:

```
POST /wp-json/simple-jwt-login/v1/auth
```

Prefer the terminal? The same setup works with [WP-CLI](#computer-wp-cli):

```bash
wp jwt config set general.decryption_key "my-super-secret"
wp jwt config set authorization.enabled 1
wp jwt login --username=admin --password=secret
```

## :tada: Features

**100% free** and **open source** under the [GPL 3.0](https://github.com/nicumicle/simple-jwt-login/blob/master/LICENSE) license - no premium tier, no upsell.

| | Feature | What it does |
|---|---|---|
| 🔑 | **JWT Authentication** | REST endpoint that generates, validates and revokes JWTs |
| 🔄 | **Refresh Tokens** | Issue and rotate refresh tokens without asking for the password again |
| 🚪 | **Autologin** | Send users from your app straight into WordPress, already logged in |
| 👤 | **User management** | Register users (with optional auto-create on first login), reset passwords with customisable emails, and delete users over REST |
| 🛡️ | **Protect endpoints** | Gate any WordPress REST endpoint behind a valid JWT, for any logged-in user or specific roles |
| 🌐 | **External API authentication** | Attach a JWT to requests for any REST endpoint and act as an authenticated user |
| 🧩 | **Custom JWT claims** | Define and read your own payload claims to pass context between your app and WordPress |
| 🗝️ | **API Keys** | Per-client keys with their own permissions and expiry, for servers and scripts |
| 📜 | **Audit Logs** | A log of every login, registration and authentication event |
| 📡 | **Webhooks** | Outbound HTTP callbacks on login, registration and authentication events |
| 💻 | **WP-CLI** | `wp jwt` commands for setup, tokens, API keys, auth codes, settings, diagnostics, logs and webhooks, built in ([details](#computer-wp-cli)) |
| 🔐 | **Social login** | Google, Facebook, GitHub and Auth0 OAuth, plus Google `id_token` authentication |
| 🔒 | **Two-Factor Authentication** | Works with the [Two Factor](https://wordpress.org/plugins/two-factor/) plugin: users with 2FA exchange an interim JWT and their TOTP, email or backup code for a full JWT |
| 🛒 | **WooCommerce** | Authenticate the WooCommerce REST API (`/wc/v1`, `/wc/v2`, `/wc/v3`) and Store API (cart and checkout) with a JWT instead of a consumer key and secret |
| 🕸️ | **WPGraphQL** | Headless frontends using GraphQL authenticate through the same plugin |
| 🚧 | **Force Login compatibility** | The plugin's own endpoints bypass the [Force Login](https://wordpress.org/plugins/force-login/) site-wide login requirement |
| 🤖 | **AI Abilities (MCP)** | AI agents (Claude, Codex, Cursor, Gemini CLI, GitHub Copilot, ...) read and manage the settings, API keys, webhooks and logs, and troubleshoot errors, with read / write access control (WordPress 6.9+) |
| 📱 | **Headless and mobile** | Built for Next.js, React, React Native, Flutter and other API-first clients that need stateless, token-based authentication |

## :bar_chart: How Simple JWT Login Compares

Most WordPress JWT plugins lock advanced features behind paid plans. Simple JWT Login ships everything below for **FREE**.

| Feature                            | Simple JWT Login | Other Plugins                         |
|------------------------------------|------------------|---------------------------------------|
| JWT Authentication                 | ✅ Free           | ✅ Free                                |
| Refresh Tokens                     | ✅ Free           | ❌ Paid or unavailable                 |
| API Keys                           | ✅ Free           | ❌ Premium only                        |
| Audit Logs                         | ✅ Free           | ❌ Premium only                        |
| Webhooks                           | ✅ Free           | ❌ Premium only                        |
| Google OAuth Login                 | ✅ Free           | ❌ Premium only                        |
| Facebook OAuth Login               | ✅ Free           | ❌ Premium only                        |
| GitHub OAuth Login                 | ✅ Free           | ❌ Premium only                        |
| Auth0 Login                        | ✅ Free           | ❌ Premium only                        |
| Generic OAuth / OIDC               | ❌ Not yet        | ⚠️ Limited or Premium                 |
| WPGraphQL Support                  | ✅ Free           | ❌ Not available                       |
| Two-Factor Authentication          | ✅ Free           | ❌ Not available                       |
| WooCommerce REST/Store API Auth    | ✅ Free           | ❌ Not available                       |
| AI Abilities / MCP                 | ✅ Free           | ❌ Not available                       |
| WP-CLI Commands                    | ✅ Free, built in | ⚠️ Limited or unavailable             |
| User Auto-create                   | ✅ Free           | ⚠️ Basic or Premium                   |
| JWT Refresh Endpoint               | ✅ Free           | ❌ Premium only                        |
| Custom JWT Claims                  | ✅ Free           | ⚠️ Via hooks or Premium               |
| Token Revocation                   | ✅ Free           | ❌ Premium only                        |
| Headless WordPress Focus           | ✅ Strong         | ⚠️ Basic or SSO-focused               |
| Mobile App Support                 | ✅ Excellent      | ⚠️ Basic or Good                      |
| External API Authentication        | ✅ Free           | ⚠️ Limited or Premium                 |
| Open Source Transparency           | ✅ Fully open     | ⚠️ Varies - some obfuscation reported |
| Setup Simplicity                   | ✅ Easy           | ⚠️ Easy to Complex                    |
| Enterprise SSO                     | ❌ Not yet        | ✅ Premium only                        |
| SAML Support                       | ❌ Not yet        | ✅ Premium only                        |
| Free Version Useful Without Upsell | ✅ Very strong    | ⚠️ Minimal or limited                 |
| Minimum PHP Version                | ✅ PHP 5.5+       | ⚠️ PHP 7.0+                           |

## :computer: WP-CLI

Set up the plugin, issue and revoke JWTs, rotate the signing key, manage API keys, auth codes and settings, read the audit log and troubleshoot from the command line - handy for scripts, CI, deployments and local development. The commands are part of the plugin (on by default, **General > CLI**): no add-on to install.

```bash
wp jwt setup                                   # signing key + enable /auth
TOKEN=$(wp jwt login --username=admin)         # asks for the password
wp jwt validate "$TOKEN"
wp jwt log list --status=failure
```

:point_right: **[Full WP-CLI reference](docs/CLI.md)** - every command and option, with examples and troubleshooting.

## :link: Integrations

Third-party integrations are configured from the **Integrations** tab in the plugin admin. Each one is disabled by default and shows a warning if the target plugin is not installed or activated.

### Two-Factor Authentication

Integrates with the [Two Factor](https://wordpress.org/plugins/two-factor/) plugin so JWT issuance also enforces any 2FA method the user has configured (TOTP, email code, or backup codes).

**How it works:**

1. Client POSTs credentials to `/auth`. If the user has 2FA configured, the plugin returns a short-lived **interim JWT** instead of a full JWT.
2. Client submits the interim JWT plus the 2FA code to `POST /auth/2fa`.
3. On success, a full JWT (and optional refresh token) is returned, identical to a normal `/auth` response.

The interim JWT TTL is configurable (default: 5 minutes). Browser-based OAuth logins (Google, Facebook, GitHub, Auth0) that hit a 2FA-enabled account are redirected to an in-page 2FA code form before the WordPress session is created.

[Read more](https://simplejwtlogin.com/docs/) on our website.

### WooCommerce

Authenticate [WooCommerce](https://woocommerce.com/) REST API (`/wc/v1`, `/wc/v2`, `/wc/v3`) and Store API (`/wc/store/v1`, including cart & checkout) requests using a JWT instead of consumer key/secret pairs.

* Works independently of the global "Protect Endpoints" middleware - JWT authentication on WooCommerce routes is always active once the integration is enabled.
* Optional **Store API cart & checkout** toggle lets header (`Authorization: Bearer`) JWT requests skip WooCommerce's Store API CSRF nonce check, enabling a fully headless cart and checkout flow. Cookie- and URL-based tokens always keep the nonce requirement, since only header tokens are immune to CSRF.

### Force Login

Lets Simple JWT Login's own REST endpoints bypass the [Force Login](https://wordpress.org/plugins/force-login/) plugin's site-wide "require login to view any page" restriction, so external clients can still reach `/auth`, `/autologin`, and other plugin routes without an existing WordPress session.

### AI Abilities (MCP)

Let AI agents (Claude, Codex, Cursor, Gemini CLI, GitHub Copilot, ...) manage the plugin through the [WordPress Abilities API](https://developer.wordpress.org/apis/abilities-api/) and the [MCP Adapter](https://github.com/WordPress/mcp-adapter): read and change settings, manage API keys and webhooks, analyze the audit log, run a security report and troubleshoot errors. Requires WordPress 6.9+.

Off by default. Turn it on in **Integrations > Third Party Integrations > AI Abilities (MCP)** and choose **Read only**, **Write only** or **Read & write** access. The riskiest actions (viewing secrets, changing roles or authentication providers, creating API keys, adding webhooks) each need their own opt-in permission, and secrets can never be written by an agent.

```bash
claude mcp add my-site -- wp mcp-adapter serve --server=mcp-adapter-default-server --user=admin --path=/path/to/wordpress
```

> "Why does my app get error_code 48?" · "Is anyone attacking my logins?" · "Is my setup secure?"

:point_right: **[Full AI Abilities guide](docs/AI-ABILITIES.md)** - setup for every AI client, each ability with its inputs and examples, REST and PHP usage, and the security model.


## :electric_plug: Integrate

| | Tool | Get started |
|---|---|---|
| 🐘 | **[PHP SDK](https://github.com/nicumicle/simple-jwt-login-client-php)** | `composer require nicumicle/simple-jwt-login-client-php` |
| 🟨 | **[JavaScript SDK](https://github.com/simple-jwt-login/js-sdk)** | `npm install simple-jwt-login` or `yarn add simple-jwt-login` |
| 💻 | **[WP-CLI](docs/CLI.md)** | Built in: `wp jwt --help` |
| 📮 | **[Postman collection](https://www.postman.com/simple-jwt-login/simple-jwt-login/overview)** | Explore and test every REST endpoint |

### PHP SDK

A Composer package to call the plugin from your PHP app or site.

```shell
composer require nicumicle/simple-jwt-login-client-php
```

See the [GitHub repository](https://github.com/nicumicle/simple-jwt-login-client-php) for details and code examples.

### JavaScript SDK

Install it with `npm` or `yarn`:

```shell
npm install "simple-jwt-login"
# or
yarn add "simple-jwt-login"
```

See the [GitHub repository](https://github.com/simple-jwt-login/js-sdk) for details.

### Postman

Explore and test the REST endpoints with the public [Postman collection](https://www.postman.com/simple-jwt-login/simple-jwt-login/overview).

## :ledger: Documentation

Plugin documentation is available at [simplejwtlogin.com](https://simplejwtlogin.com).

| Getting started | Endpoints | Advanced |
|---|---|---|
| [Introduction](https://simplejwtlogin.com/docs/) | [Authentication](https://simplejwtlogin.com/docs/authentication) | [Protect Endpoints](https://simplejwtlogin.com/docs/protect-endpoints) |
| [Configuration](https://simplejwtlogin.com/docs/configuration) | [Autologin](https://simplejwtlogin.com/docs/autologin) | [Hooks](https://simplejwtlogin.com/docs/hooks) |
| [Error codes](https://simplejwtlogin.com/docs/error-codes/) | [Register User](https://simplejwtlogin.com/docs/register-user) | [Code examples](https://simplejwtlogin.com/docs/code-examples/) |
| | [Reset Password](https://simplejwtlogin.com/docs/reset-password) | |
| | [Delete User](https://simplejwtlogin.com/docs/delete-user) | |

## :rocket: Roadmap

Check out the [roadmap](https://github.com/users/nicumicle/projects/1) to get informed on the latest released features, current statuses, and upcoming features.

- [Top Feature Requests](https://github.com/nicumicle/simple-jwt-login/issues?q=label%3Afeature-request+is%3Aopen+sort%3Areactions-%2B1-desc) (Add your votes using the 👍 reaction)
- [Top Bugs](https://github.com/nicumicle/simple-jwt-login/issues?q=is%3Aissue+is%3Aopen+label%3Abug+sort%3Areactions-%2B1-desc) (Add your votes using the 👍 reaction)
- [Newest Bugs](https://github.com/nicumicle/simple-jwt-login/issues?q=is%3Aopen+is%3Aissue+label%3Abug)

## :calendar: Version Support Policy

| Version | Status | End of Life | Supported fixes |
|---------|--------|-------------|------------------|
| v4.x | Active (LTS) | - | New features, bug fixes, security patches, WordPress compatibility fixes |
| v3.x | Maintenance | 31.01.2027 | Security patches and WordPress compatibility fixes only - no new features, no bug fixes |

We recommend upgrading to v4 as soon as possible. After 31.01.2027, v3.x will no longer receive any updates, including security patches.

<details>
<summary><b>Semantic Versioning</b></summary>

Simple-JWT-Login follows [Semantic Versioning (SemVer)](https://semver.org/) - version numbers are structured as `MAJOR.MINOR.PATCH` (for example `4.2.1`):

- **MAJOR** (`4.x.x`) - incremented for breaking/incompatible changes, such as removing a feature or changing default behavior. Upgrading a major version may require reviewing your settings or integration code.
- **MINOR** (`x.2.x`) - incremented when new functionality is added in a backwards-compatible way.
- **PATCH** (`x.x.1`) - incremented for backwards-compatible bug fixes and security patches.

For v3.x, since it's now in maintenance mode, only PATCH releases will be published (security and WordPress compatibility fixes) - no new MINOR versions will be released.

</details>

## :scroll: Contribute

Simple-JWT-Login is an open-source project and welcomes all contributors. As with all WordPress projects, we want to ensure a welcoming environment for everyone, so all contributors are expected to follow our [Code of Conduct](https://github.com/nicumicle/simple-jwt-login/blob/master/CODE_OF_CONDUCT.md).

**How you can contribute:**

- 🛠️ Open pull requests on existing issues: [CONTRIBUTING.md](https://github.com/nicumicle/simple-jwt-login/blob/master/CONTRIBUTING.md)
- 💡 Suggest features or report bugs: [issues/bugs](https://github.com/nicumicle/simple-jwt-login/issues/new/choose)
- 🌍 Translate the plugin: [translate.wordpress.org](https://translate.wordpress.org/projects/wp-plugins/simple-jwt-login/)

## :trophy: Contributors

Thanks to all our contributors!

<a href="https://github.com/nicumicle/simple-jwt-login/graphs/contributors">
  <img src="https://contrib.rocks/image?repo=nicumicle/simple-jwt-login" alt="Contributors"/>
</a>

## :blue_heart: Sponsors

This project is supported by:

<a href="https://www.digitalocean.com/">
  <img src="https://opensource.nyc3.cdn.digitaloceanspaces.com/attribution/assets/SVG/DO_Logo_horizontal_blue.svg" height="40" alt="DigitalOcean"></a>
&nbsp;&nbsp;
<a href="https://coderabbit.link/nicumicle">
  <picture>
      <source media="(prefers-color-scheme: dark)" srcset="https://www.coderabbit.ai/press-kit/coderabbit-reverse-white.svg">
      <source media="(prefers-color-scheme: light)" srcset="https://www.coderabbit.ai/press-kit/coderabbit-primary-black.svg">
      <img src="https://www.coderabbit.ai/press-kit/coderabbit-primary-black.svg" height="40" alt="CodeRabbit">
  </picture></a>

## Copyright

This project is distributed under the [GNU General Public License v3.0](https://github.com/nicumicle/simple-jwt-login/blob/master/LICENSE).

By submitting a pull request to this project, you agree to license your contribution under the GNU General Public License v3.0 to this project.
