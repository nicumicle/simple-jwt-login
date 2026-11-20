=== Simple JWT Login - JWT Authentication for REST API ===

Contributors: nicu_m
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=PK9BCD6AYF58Y&source=url
Tags: jwt, rest api, headless, oauth, woocommerce
Requires at least: 6.2
Tested up to: 7.1
Requires PHP: 5.5
Stable tag: 4.2.0
License: GPLv3
License URI: https://github.com/nicumicle/simple-jwt-login/blob/master/LICENSE

Modern JWT authentication for WordPress REST APIs, headless frontends, mobile apps, WooCommerce and WPGraphQL - fully free, no upsells.

== Description ==

**Log users in, register them, and protect your WordPress REST API with JSON Web Tokens - all from a friendly admin screen, without writing a single line of PHP.**

Simple JWT Login turns WordPress into a secure backend for your headless frontend, mobile app, or external service. Next.js, React, Vue, React Native, Flutter, a Node.js server or a CI pipeline: if it can send an HTTP request, it can authenticate with your WordPress site.

And it is **100% free and open source**. Refresh tokens, API keys, audit logs, webhooks, OAuth social login and two-factor authentication are all included. No Pro plan, no feature locks, no telemetry.

Trusted on **4,000+ WordPress sites**, rated **5 stars** on WordPress.org, and actively maintained since 2019.

> "This is by far the best JWT plugin for headless. I've tried them all!"
> - a developer, in a [5-star WordPress.org review](https://wordpress.org/support/plugin/simple-jwt-login/reviews/)

👉 [Quick Start](https://simplejwtlogin.com/docs/quick-start/) · [Documentation](https://simplejwtlogin.com/docs/) · [API Reference](https://simplejwtlogin.com/api/v4/simple-jwt-login) · [Live Demos](https://simplejwtlogin.com/demos/)

= ⚡ See how simple it is =

Send your credentials, get a token back:

    curl -X POST \
      https://your-site.com/wp-json/simple-jwt-login/v1/auth \
      -d email=jane@example.com \
      -d password=secret

The response:

    {
      "success": true,
      "data": {
        "jwt": "eyJ0eXAiOiJKV1Qi...",
        "refresh_token": "a1b2c3d4..."
      }
    }

Turn on **JWT Middleware for all WordPress endpoints**, then call any WordPress REST endpoint as that user:

    curl https://your-site.com/wp-json/wp/v2/users/me \
      -H "Authorization: Bearer eyJ0eXAiOiJKV1Qi..."

That's the whole integration. Everything else - refresh, auto-login, social login, protected routes - is a toggle away.

⭐ Like what you see? [Star Simple JWT Login on GitHub](https://github.com/nicumicle/simple-jwt-login) and browse the source.

= ✨ Why developers choose Simple JWT Login =

* **No code required** - every feature is a toggle in a clean, tab-based admin UI.
* **Everything is free** - the features other plugins sell as "Pro" ship here at no cost.
* **Up and running in five minutes** - copy the auto-generated sample URLs and try them right in the built-in endpoint tester.
* **Secure by default** - every endpoint starts disabled, and a security report on the dashboard points out risky settings.
* **Runs on any host** - from PHP 5.5 all the way to PHP 8, on WordPress 6.2 and newer.
* **Truly open source** - GPL 3.0, no obfuscated code, built in the open on GitHub.

= 🧰 Everything in one plugin =

* **Authentication** - generate, refresh, validate and revoke JWTs, with HS or RS signing and custom claims. [Learn more](https://simplejwtlogin.com/docs/authentication/)
* **Auto-login** - send users from your app straight into WordPress, already logged in. [Learn more](https://simplejwtlogin.com/docs/autologin/)
* **User management** - [register](https://simplejwtlogin.com/docs/register-user/), [reset passwords](https://simplejwtlogin.com/docs/reset-password/) and [delete accounts](https://simplejwtlogin.com/docs/delete-user/) over REST, with [auth codes](https://simplejwtlogin.com/docs/auth-codes/) for extra protection.
* **Social login** - Google, Facebook, GitHub and Auth0. [Learn more](https://simplejwtlogin.com/docs/integrations/oauth/)
* **Protected endpoints** - choose who can reach each REST route: public, any logged-in user, or specific roles. [Learn more](https://simplejwtlogin.com/docs/protect-endpoints/)
* **CORS** - let your frontend's domain call the API safely. [Learn more](https://simplejwtlogin.com/docs/cors/)
* **API keys** - credentials with their own permissions and expiry for servers and scripts. [Learn more](https://simplejwtlogin.com/docs/api-keys/)
* **Audit logs and webhooks** - see every login attempt and notify your other systems. [Audit logs](https://simplejwtlogin.com/docs/audit-logs/) · [Webhooks](https://simplejwtlogin.com/docs/webhooks/)

= 🧩 Plays well with your favorite plugins =

* 🛒 **WooCommerce** - build a headless store: cart, checkout and customer accounts over the REST and Store APIs, authenticated with a JWT and no session cookies. [Learn more](https://simplejwtlogin.com/docs/integrations/third-party/woocommerce/)
* 🔐 **Two Factor** - users who have 2FA turned on send their code to `/auth/2fa` before they get a token, so your app is as safe as wp-login.php. [Learn more](https://simplejwtlogin.com/docs/integrations/third-party/two-factor/)
* 🕸️ **WPGraphQL** - run authenticated GraphQL queries and mutations as the logged-in user. [Learn more](https://simplejwtlogin.com/docs/integrations/third-party/wpgraphql/)
* 🚪 **Force Login** - keep your site private while your app can still reach the login endpoints. [Learn more](https://simplejwtlogin.com/docs/integrations/third-party/force-login/)
* 🤖 **AI Abilities (MCP)** - let AI agents check, configure and troubleshoot the plugin, with access you control. [Learn more](https://simplejwtlogin.com/docs/integrations/third-party/ai-abilities/)

= 🛠️ Built for developers =

[Hooks](https://simplejwtlogin.com/docs/hooks/) · [PHP SDK](https://simplejwtlogin.com/ecosystem/php-sdk) · [JavaScript SDK](https://simplejwtlogin.com/ecosystem/js-sdk) · [CLI](https://simplejwtlogin.com/ecosystem/cli) · [Code examples](https://simplejwtlogin.com/docs/code-examples/) · [Error codes](https://simplejwtlogin.com/docs/error-codes/)

= 🔒 Security you can verify =

Authentication is only as good as its maintenance. Security reports are handled through responsible disclosure (Patchstack) and fixed within days, and every fix ships with a regression test that reproduces the original issue.

* Unit, integration and real-browser cross-site (CSRF and CORS) test suites run on every change
* Static analysis with PHPStan and PHPMD, and WordPress coding standards
* Brute-force protection plugins (Limit Login Attempts, Wordfence) cover the login endpoint just like `wp-login.php`
* The open source code is on [GitHub](https://github.com/nicumicle/simple-jwt-login) - read it, audit it, contribute

= 💚 Free, and staying free =

Here is what you get compared to the typical WordPress JWT plugin:

* **JWT authentication** - free here, free elsewhere
* **Refresh tokens** - free here, often paid or missing elsewhere
* **API keys, audit logs, webhooks** - free here, premium elsewhere
* **Google, Facebook, GitHub and Auth0 login** - free here, premium elsewhere
* **WPGraphQL, WooCommerce and two-factor support** - free here, rarely available elsewhere
* **Custom JWT claims** - free from the admin screen here, code or premium elsewhere

If it saves you time, a [donation](https://simplejwtlogin.com/donate) or a [5-star review](https://wordpress.org/support/plugin/simple-jwt-login/reviews/#new-post) keeps the project going.

== Installation ==

1. In your WordPress admin, go to **Plugins > Add New** and search for "Simple JWT Login".
2. Click **Install Now**, then **Activate**.
3. Open **Settings > Simple JWT Login**.
4. In the **General** tab, set a long, random **JWT Decryption Key** and pick an algorithm (HS256 is a safe default).
5. Enable the features you need - every endpoint is off until you turn it on.

That's it. Each tab shows ready-to-copy sample URLs and a **Try it out** button, so you can test your first request without leaving WordPress.

Follow the [Quick Start guide](https://simplejwtlogin.com/docs/quick-start/) for a step-by-step walkthrough, or read about every option in the [Configuration guide](https://simplejwtlogin.com/docs/configuration).

== Frequently Asked Questions ==

= Do I need to write code? =

No. Everything is configured from the admin screen. Your app only needs to send HTTP requests - and the [SDKs](https://simplejwtlogin.com/ecosystem/) make even that easier.

= Is it really free? =

Yes. Every feature is free and the code is open source under GPL 3.0. There is no Pro version.

= Is this plugin secure? =

Yes. Every token is checked against your key and algorithm before anything happens, and all endpoints are disabled by default. For production, use a long random key, enable auth codes on sensitive endpoints, and keep an eye on the dashboard's security notices. See the [Configuration guide](https://simplejwtlogin.com/docs/configuration) for recommendations.

= Can I use it with a mobile app or a headless frontend? =

Absolutely - that is what it is built for. Your app gets a JWT from the [authentication endpoint](https://simplejwtlogin.com/docs/authentication/) and sends it with every request. Check out the [code examples](https://simplejwtlogin.com/docs/code-examples/) and [live demos](https://simplejwtlogin.com/demos/).

= Why not just use WordPress Application Passwords? =

Application Passwords are long-lived secrets sent with every request, built for trusted server-to-server tools. Simple JWT Login gives your app short-lived tokens that refresh and can be revoked, so no password is ever stored on a user's device. On top of that you get social login, auto-login, user registration and per-route protection. See the [Authentication guide](https://simplejwtlogin.com/docs/authentication/).

= Does it work with WPGraphQL and WooCommerce? =

Yes. Enable each one from the Integrations tab. See the [integrations guide](https://simplejwtlogin.com/docs/integrations/third-party/).

= Can I limit who can register? =

Yes. Restrict registration to specific email domains or IP addresses, and use auth codes to assign different roles. See [Register users](https://simplejwtlogin.com/docs/register-user/).

= Can I accept JWTs issued by another service? =

Yes. Any token signed with the key and algorithm you configure is accepted, and JWT rules let you trust several issuers with different keys. See the [Configuration guide](https://simplejwtlogin.com/docs/configuration).

= I get an error. Where do I start? =

Every response includes an `error_code`. Look it up in the [error codes list](https://simplejwtlogin.com/docs/error-codes/) for the cause and the fix. Still stuck? Ask on the [support forum](https://wordpress.org/support/plugin/simple-jwt-login/) or [open an issue on GitHub](https://github.com/nicumicle/simple-jwt-login/issues).

== Screenshots ==

1. Dashboard - every route's status at a glance, with security notices for risky settings
2. General - signing algorithm and keys, user identification, and where tokens are read from
3. Login - auto-login, redirects after login, and access control
4. Register - registration endpoint, default roles, random passwords, and allowed user meta
5. Delete User - delete endpoint with auth code and IP restrictions
6. Reset Password - reset and change password flow with customizable emails
7. Authenticate - JWT generation with a live payload preview, issuer, audience, and expiration
8. Refresh Token - refresh token endpoint and token lifetime
9. Validate Token - validate token endpoint
10. Revoke Token - revoke token endpoint
11. Auth Codes - authorization codes with a per-code role and expiration date
12. Protect Endpoints - ordered access rules: public, JWT required, or JWT + roles
13. CORS - allowed origins, methods, and headers
14. API Keys - create keys with permissions and expiry, and manage existing keys
15. Revoked Tokens - every revoked token with its expiration date
16. OAuth - Google, Auth0, Facebook, and GitHub login, plus the login button layout
17. Third-Party Integrations - WPGraphQL, Two Factor, Force Login, WooCommerce, and AI Abilities (MCP)
18. Webhooks - outbound HTTP calls on login, registration, and authentication
19. Webhook Logs - the result of every webhook call, with a retention period
20. Audit Logging - choose which events to record and how long to keep them
21. Activity Log - filterable history of every login, registration, and authentication event
22. Hooks - every WordPress action and filter the plugin exposes
23. JWT Decoder - inspect a token's header and payload right in your browser

== Changelog ==

A complete changelog is available on the [GitHub repository](https://github.com/nicumicle/simple-jwt-login/blob/master/Changelog.md).

= 4.2.0 (19 November 2026) =
- Security: Fixed multiple security vulnerabilities affecting authentication, authorization, and REST API endpoints.
- Security: Added security regression tests reproducing previously identified vulnerabilities to prevent regressions.
- Security: Strengthened validation and authorization mechanisms across affected endpoints.
- Security: Improved overall security and robustness.
- Security: Improved the data returned by REST API endpoints.
- New option "Revoke sessions on password change" (Authentication, off by default). When on, changing a password deletes the user's refresh tokens and rejects every JWT issued before the change. JWTs without an `iat` claim are then also rejected for that user
- New optional "Expected audience (aud)" setting for the plugin's own JWTs and for each JWT Rule. When set, new JWTs carry that `aud` and only JWTs whose `aud` contains it are accepted (new error code 130)
- New OAuth options, off by default, to accept tokens that are not bound to the configured app, for backward compatibility: "Allow tokens issued for other Google clients", "Allow tokens issued for other Facebook apps", "Allow tokens issued for other GitHub apps" (also accepts personal access tokens) and Auth0 "Allow opaque access tokens". The admin screen shows a security warning while one is on: any app on that provider could then log its users into the matching WordPress accounts
- Dashboard: new "Security notices" section listing the enabled options and settings that weaken security (critical and warning findings of the security report), most severe first. Clicking a notice opens the matching settings tab. The dashboard and the AI `security-report` ability also report the OAuth options above when they are on
- `/auth` now checks the password with `wp_authenticate()`, so brute-force and user-block plugins (Limit Login Attempts, Wordfence, ...) protect it like `wp-login.php`. Application passwords can not be exchanged for a JWT
- The security report now warns when authentication with `password_hash` is enabled
- Admin: optional support banner with a donation link at the top of the settings page. It can be dismissed for 6 months and no features are locked behind donations
- OpenAPI: the endpoints used by the plugin admin screens (`/api-keys*`, `/revoked-tokens*`) are tagged `internal`, with their access rules (revoked tokens: administrators only; API keys: own keys, administrators manage every user's keys)
- OpenAPI: every endpoint now documents its real request parameters, status codes and error codes, with examples taken from the plugin messages
- Breaking: WordPress 6.2 or newer is required (was 4.4). The plugin's database queries use the `%i` identifier placeholder added in WordPress 6.2
- Breaking: The OAuth login buttons (Google, Auth0, Facebook, GitHub) now go through the plugin, which redirects to the provider. Custom login buttons or links that send visitors straight to the provider with the plugin's callback as `redirect_uri` stop working. Link to `GET /oauth/token?provider=<provider>` instead
- Breaking: Tokens sent to the OAuth endpoint (and Google `id_token`s used on all endpoints) must be issued for the app configured in the plugin: a Facebook or GitHub `access_token` must belong to the configured app (GitHub personal access tokens are rejected), a Google `id_token` must have the configured Client ID as `aud`, and an Auth0 `access_token` must be a JWT issued for the configured Auth0 Client ID (`azp` or `aud`), so opaque Auth0 access tokens are rejected. Sites that need the old behavior can turn on the matching new option (see New)
- Breaking: `POST /user/reset_password` with an unknown email now answers 200 instead of 404 "Wrong user."
- Breaking: With "Trust reverse proxy IP headers" on, the client IP is read from the last `X-Forwarded-For` entry only. Sites whose proxy sends the client IP only in `Client-IP` must switch the proxy to `X-Forwarded-For`
- Breaking: After too many wrong two-factor codes (30 by default, Two Factor filter `two_factor_failed_attempt_limit`) sent to `POST /auth/2fa` or the OAuth two-factor form, the user's password is reset and the user and admin are emailed, as on `wp-login.php`
- Breaking: API keys used on `/batch/v1` need the create, update and delete permissions, otherwise they get 401
- Breaking: Client errors that answered HTTP 500 now answer their real status (`error_code` values are unchanged): 401 for 2FA session/code errors (106, 107, 109, 110), JWT without the "JWT login by" claim (29, 30), Facebook and GitHub invalid code or token (98, 99, 102, 103) and Google `id_token` for another client (118); 403 for revoked token endpoints called by a non-administrator (115) and unverified OAuth emails (111, 112, 119); 404 for Facebook and GitHub user not found (100, 104) and unknown revoked token id (116); 422 for invalid OAuth provider or missing code/token (69, 71, 77, 97, 101), missing reset password code (60) and invalid API key name or permissions (85, 86, 87); 429 for too many wrong 2FA codes (108)
- Breaking: Failing to store the 2FA session now has its own error code 133 (HTTP 500); it used to share code 107 with "2FA session expired"
- Fix the Facebook browser login returning a 500 error instead of the login page with the error message when Facebook rejects the code
- Fix two parallel `/auth/refresh` calls with the same refresh token both getting a new JWT: a refresh token can now be used once only
- Fix API keys and refresh tokens staying in the database when a user is deleted in wp-admin, and an API key of a deleted user causing a 500 error instead of 401
- Fix `/auth` rejecting base64 passwords that contain `'`, `"` or `\`. Invalid base64 now answers "Wrong user credentials."
- Fix `/auth` and `/auth/refresh` returning a refresh token that could not be saved: they now answer 500 (new error code 131)
- Fix API key `expires_at` accepting any text: it must be a date, a date time or an ISO 8601 date (new error code 132), is read as UTC and stored as UTC. The admin form now sends the browser's local time converted to UTC
- Fix the Delete button in Security > Revoked Tokens, which always failed with "Invalid revoked token ID.": `DELETE /revoked-tokens/{id}` now receives the id from the URL path (it answered 500, `error_code` 116)

= 4.1.1 (15 November 2026) =
- Security: a JWT read from a cookie, the session or the Authorization header no longer authenticates cross-site requests (including sibling subdomains and other ports), fixing CSRF on cookie-authenticated REST requests when the API middleware is enabled (Reported via Patchstack)
- Breaking: cross-origin browser apps that send the JWT must have their origin listed in the CORS "Allow origin" setting. A "*" entry trusts the Authorization header only, never the cookie or session
- Breaking: with CORS disabled (or "Allow origin" off), a JWT sent by a browser page on another site, even in the Authorization header, is ignored. Requests from the site itself, server-to-server calls, mobile apps and JWTs passed in the URL (e.g. autologin links) are not affected
- Fix "JWT from session" making every plugin endpoint and Protect Endpoints fail with a 500 error (call to an undefined session function)

= 4.1.0 (11 November 2026) =
- Security: fix CSRF in the Force Login integration on cookie-authenticated REST requests (Reported via Patchstack)
- Security: JWT middleware and the WooCommerce integration now check the resolved REST route instead of searching the URL
- Security: Protect Endpoints now enforces "JWT + Roles" rules for logged-in (cookie-authenticated) users and API key owners
- New integration: AI Abilities (MCP) - let AI agents manage and troubleshoot the plugin through the WordPress Abilities API (WordPress 6.9+)
- Fix secrets (auth codes, JWT rule keys, webhook header values) being written in plain text to the "Settings Saved" audit log entry
- Security: registration never sets capability, user level or session meta keys from the request, even when they are listed in "Allowed user meta"
- Security: webhook calls time out after at most 30 seconds (0 now means 30 seconds instead of no timeout), so a slow webhook endpoint cannot hang logins
- Fix login creating two WordPress sessions instead of one (autologin, register with auto-login, OAuth), reducing DB writes per login
- Performance: API key "last used" timestamp is now updated at most once per minute per key, instead of on every request. A new Update "Last used" setting (API Keys) lets you switch back to updating it on every request
- Performance: remove an extra database query on every API key request
- WooCommerce integration: authenticate JWT requests without a full login, so API calls no longer create a new WordPress session, set auth cookies or fire `wp_login` on every request
- Performance: load the WordPress admin user functions only when deleting a user, instead of on every request
- Performance: Protect Endpoints no longer checks the revoked-token table twice for every JWT-authenticated request
- Performance: the Force Login, WooCommerce and WPGraphQL integrations reuse the plugin settings instead of re-reading and decoding them on every REST request
- Performance: Protect Endpoints checks access once per REST request instead of every time WordPress reads the route list
- Fix CORS headers missing on REST error responses (401/403 from JWT auth and protected endpoints) [#179](https://github.com/nicumicle/simple-jwt-login/issues/179)
- CORS: support multiple allowed origins (comma-separated); the matching request origin is echoed back, and `*` echoes the request origin so credentialed requests work [#179](https://github.com/nicumicle/simple-jwt-login/issues/179)

= 4.0.2 (09 October 2026) =
- Fix WooCommerce integration [#177](https://github.com/nicumicle/simple-jwt-login/issues/177)
- Update WordPress 7.1 compatibility

= 4.0.1 (09 August 2026) =
- Apply protect endpoints rules only on REST endpoints
- Improve Google Oauth Security (Reported by [@FaridNarimanov](https://github.com/FaridNarimanov)) [#174](https://github.com/nicumicle/simple-jwt-login/issues/174)
- JWT Payload: strip reserved claims improvement

= 4.0.0 (06 July 2026) =
- Redesign Protect Endpoints: replace separate whitelist/protected lists with a unified ordered rules table where each rule explicitly sets access to Public, JWT required, or JWT + Roles; rules are evaluated top-to-bottom with first-match-wins; old settings auto-migrate;
- Redesigned Plugin Settings interface 
- Fix double encoding for Reset Password email body[#165](https://github.com/nicumicle/simple-jwt-login/issues/165)
- New refresh token handling
- Add Audit logs
- Add support for multiple JWT Decryption keys based on JWT header or payload
- Move User identification from Login to General
- Add Webhooks
- Enable/disable Revoke and Validate token 
- Add support for API Keys (X-API-Key) to access WordPress endpoints as an authenticated user 
- Allow JWT authentication on the API Keys endpoint (in addition to cookie-based login)
- Improve HTTP Status Codes for errors
- Add Auth0, Facebook and GitHub Oauth
- **BREAKING CHANGE** Rename errorCode to error_code in error responses
- **BREAKING CHANGE** Add 2FA support
- **BREAKING CHANGE** Add refresh token to Authentication
- **BREAKING CHANGE** Remove User Identification from Delete, and use the one from General 
- **BREAKING CHANGE** Reorganize settings structure in DB
- **BREAKING CHANGE** Change register user response ({"success":true, "ID": 1,...}) to {"success":true, "data":{"id":..}}
- **BREAKING CHANGE** Remove old /register user endpoint (only POST /users route available for register) 
- Fix register, authenticate, and reset password special characters for passwords
- Replace variables in reset password email subject
- Add option for sending default WordPress email on user registration
- Add option for sending default password change WordPress email when change password endpoint is called
- Improve plugin security and performance
- Add Light/Dark mode UI
- Allow non admin users to manage their own API keys
- Add option for 'Require "Bearer" prefix' [#96](https://github.com/nicumicle/simple-jwt-login/issues/96)
- Support multiple default user roles on registration
- Add Full WooCommerce support

= 3.6.7 (03 July 2026) =
- Improve plugin security

= 3.6.6 (22 May 2026) =
- Update WordPress 7.0 Compatibility

= 3.6.5 (14 Mar 2026) =
- Fix  CVE-2025-58648 - Stored Cross-Site Scripting vulnerability[PR](https://github.com/nicumicle/simple-jwt-login/pull/162)
- Fix for bug: Reset password function doesn't use base64 encoding logic and doesn't allow user to use any special character [#161](https://github.com/nicumicle/simple-jwt-login/issues/161) [#163](https://github.com/nicumicle/simple-jwt-login/pull/163)
- Fix PHP session initialization warning [#159](https://github.com/nicumicle/simple-jwt-login/issues/159)
- Update WordPress 6.9 Compatibility

= 3.6.4 (17 Apr 2025) =
- Update WordPress 6.8 Compatibility

= 3.6.3 (15 Apr 2025) =
- Fix protect endpoints [#149](https://github.com/nicumicle/simple-jwt-login/issues/149)

= 3.6.2 (09 Apr 2025) =
- Fix blocked backened endpoints by ProtectEndpoints [#146](https://github.com/nicumicle/simple-jwt-login/issues/146)
- Ensure Protect endpoints searches for JWT in session

= 3.6.1 (02 Apr 2025) =
- Validate JWT on protect endpoints[#141](https://github.com/nicumicle/simple-jwt-login/issues/141)
- Fix backward compatibility for protect endpoints. Add `match`/`start_with` option for endpoints.[#143](https://github.com/nicumicle/simple-jwt-login/issues/143)

= 3.6.0 (24 Mar 2025) =
- Add support for configuring request methods on Protect endpoints [#129](https://github.com/nicumicle/simple-jwt-login/issues/129)
- Beta: Authenticate user when performing queries with WPGraphQL [#32](https://github.com/nicumicle/simple-jwt-login/issues/32)
- Code refactorization and UI improvements
- Authenticate by Username or Email (similar to WP login)[#19](https://github.com/nicumicle/simple-jwt-login/issues/19)

= 3.5.8 (14 Feb 2025) =
- Use wp_safe_redirect for redirects [#115](https://github.com/nicumicle/simple-jwt-login/issues/115)
- Ensure JWT middleware only run once [#125:](https://github.com/nicumicle/simple-jwt-login/issues/125)

= 3.5.7 (22 Dec 2024) =
- Update WordPress 6.7 Compatibility

= 3.5.6 (03 Aug 2024) =
- Update WordPress 6.6 Compatibility
- Fix revoked token validation when middleware enabled [#110](https://github.com/nicumicle/simple-jwt-login/issues/110)

= 3.5.5 (04 May 2024) =
- Update README
- Refactor Protect Endpoints

= 3.5.4 (03 May 2024) =
- Add OAuth support for Google [#97](https://github.com/nicumicle/simple-jwt-login/issues/97)
- Fix status code for expired tokens [#102](https://github.com/nicumicle/simple-jwt-login/issues/102)
- Update WordPress 6.5 Compatibility

= 3.5.3 (16 November 2023) =
- Fix licence in composer.json
- Update WordPress 6.4 compatibility

= 3.5.2 (02 November 2023) =
- Fix change user password with revoked JWT
- Change routes priority from floats to int and fix deprecation message "Implicit conversion from float to int loses precision"
- Add `iss` to JWT payload and allow to configure it
- Fix user meta on register user [#86](https://github.com/nicumicle/simple-jwt-login/issues/86)
- Fix calling protected endpoints with revoked token [#75](https://github.com/nicumicle/simple-jwt-login/issues/75)

= 3.5.1 (1 October 2023) =
- Update WordPress 6.3 compatibility

= 3.5.0 (04 Jan 2023) =
- Fix unable to create post issue when protect endpoints is enabled for all endpoints
- Search user by email on reset password
- Drop support for PHP 5.3 and PHP 5.4

For the full history back to 1.0.0 (2019), see the [Changelog on GitHub](https://github.com/nicumicle/simple-jwt-login/blob/master/Changelog.md).

== Upgrade Notice ==

= 4.2.0 (19 November 2026) =
Important security release. Fixes multiple security vulnerabilities affecting authentication, authorization and REST API endpoints. All users are strongly encouraged to upgrade to this version as soon as possible. Review the Breaking changes in the changelog before upgrading.

= 4.1.1 (15 November 2026) =
Security fix (CSRF): a JWT no longer authenticates requests from other sites. Cross-origin browser apps must be listed in the CORS "Allow origin" setting - update recommended.

= 4.1.0 (11 November 2026) =
New AI Abilities (MCP) integration, several security fixes and performance improvements - recommended update.

= 4.0.0 (06 July 2026) =
New UI plus API Keys, Audit Logs, Facebook/GitHub OAuth, and WPGraphQL integration. Includes breaking changes - review the Settings after updating.

= 3.6.7 (06 July 2026) =
Fixes security. Update is strongly recommended for all sites.
