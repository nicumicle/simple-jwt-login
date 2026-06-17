=== Simple JWT Login ===

Contributors: nicu_m
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=PK9BCD6AYF58Y&source=url
Tags: jwt, authentication, refresh token, api keys, headless wordpress
Requires at least: 4.4.0
Tested up to: 7.0
Requires PHP: 5.5
Stable tag: 4.0.0
License: GPLv3
License URI: https://github.com/nicumicle/simple-jwt-login/blob/master/LICENSE

Modern JWT authentication for WordPress REST APIs, headless frontends, mobile apps, and WPGraphQL - fully free, no upsells.

== Description ==

**Simple JWT Login** is a 100% FREE, fully open-source WordPress plugin that provides a complete JWT authentication framework for your WordPress REST API.

Most competing plugins gate advanced features like refresh tokens, audit logs, API keys, and OAuth social logins behind expensive paid plans. Simple JWT Login ships every one of those features for free - no upsells, no feature locks, no vendor lock-in.

Whether you are building a headless WordPress site with Next.js or React, a mobile app with React Native or Flutter, or connecting external services to your WordPress backend, Simple JWT Login gives you a fast, secure, and transparent authentication layer.

Full documentation is available at [https://simplejwtlogin.com](https://simplejwtlogin.com).

**Why Simple JWT Login?**

* **No coding required.** Every feature is configured entirely through the WordPress admin UI - no PHP, no custom code, no file editing needed.
* Everything is free. Refresh tokens, audit logs, API keys, webhooks, and OAuth social logins all ship without a Pro tier.
* Multiple OAuth providers out of the box: Google, Facebook, GitHub, and Auth0.
* Works with WPGraphQL so headless frontends can authenticate GraphQL queries using the same plugin.
* PHP 5.5+ compatible - no forced PHP 7+ upgrade required.
* Fully open source under GPL 3.0 - no obfuscated code, no telemetry.

== Zero Code Required ==

Everything in Simple JWT Login is configured through a clean, tab-based admin UI inside WordPress. There is nothing to hard-code, no configuration files to edit, and no PHP snippets required to get started.

* Enable or disable any feature with a single toggle
* Copy auto-generated sample endpoint URLs directly from the admin to test in your browser or API client
* Manage API keys, audit logs, auth codes, webhooks, and OAuth providers all from the same settings page
* Live URL preview updates as you type your custom redirect or endpoint configuration

Whether you are a developer or a non-technical WordPress admin, you can have JWT authentication running in under five minutes.

== Features ==

**Authentication & Tokens**

* Generate, validate, and revoke JWTs via a dedicated REST endpoint
* Refresh tokens - renew a JWT without asking for credentials again, with automatic rotation
* Custom JWT claims - embed arbitrary payload data your app can read after decoding
* Auth codes - add an extra protection layer to any endpoint, with optional expiration dates and per-code user roles
* API Keys - issue per-client keys as an alternative credential for obtaining JWTs
* Authenticate with WordPress email, username, or password hash
* Token delivered via URL parameter, Authorization header, Cookie, or Session
* Supports HS256, HS384, HS512, RS256, RS384, RS512 signing algorithms
* Configurable JWT issuer (`iss` claim)

**User Management**

* Auto-login to WordPress with a valid JWT - redirect to dashboard, homepage, or any custom URL
* Register new users via REST endpoint with optional auto-create on first OAuth login
* Delete WordPress users based on JWT payload (by email or user ID)
* Reset and change user passwords via REST endpoints with customizable email templates
* Set custom `user_meta` fields during user registration
* Limit all operations by IP address allowlist
* Limit user registration by email domain allowlist
* Auto-login redirect supports dynamic URL variables: `{{user_id}}`, `{{user_email}}`, `{{user_login}}`, `{{user_first_name}}`, `{{user_last_name}}`, `{{user_nicename}}`, `{{site_url}}`

**OAuth & Social Login**

* **Google OAuth** - log in with a Google account via OAuth 2.0
* **Google JWT** - accept a Google `id_token` directly on WordPress REST endpoints
* **Facebook OAuth** - log in with a Facebook account via OAuth
* **GitHub OAuth** - log in with a GitHub account via OAuth
* **Auth0** - authenticate users through an Auth0 tenant

**Visibility & Integrations**

* Audit logs - detailed records of every login, registration, and authentication event for compliance and debugging
* Webhooks - fire outbound HTTP callbacks on login, register, and authentication events to connect with external services
* Protect endpoints - require a valid JWT to access any WordPress REST route, with per-endpoint or global configuration
* HTTP method filtering on protected endpoints (GET, POST, PUT, DELETE, etc.)
* External API authentication - act as an authenticated WordPress user on any REST endpoint by attaching a JWT
* **WPGraphQL** - authenticate GraphQL queries so headless frontends powered by WPGraphQL work out of the box
* CORS configuration for all plugin routes
* Extensible hooks system for custom workflows
* Configurable REST API namespace

== How Simple JWT Login Compares ==

Most WordPress JWT plugins lock advanced features behind paid plans. Here is what you get for **free** with Simple JWT Login compared to the typical alternative:

* JWT Authentication - free vs. free elsewhere
* Refresh Tokens - FREE here vs. paid or unavailable elsewhere
* API Keys - FREE here vs. premium only elsewhere
* Audit Logs - FREE here vs. premium only elsewhere
* Webhooks - FREE here vs. premium only elsewhere
* Google OAuth Login - FREE here vs. premium only elsewhere
* Facebook OAuth Login - FREE here vs. premium only elsewhere
* GitHub OAuth Login - FREE here vs. premium only elsewhere
* Auth0 Login - FREE here vs. premium only elsewhere
* WPGraphQL Support - FREE here vs. not available elsewhere
* Custom JWT Claims - FREE here vs. hooks or premium elsewhere
* Headless WordPress - strong focus here vs. basic or SSO-focused elsewhere
* PHP 5.5+ support - yes here vs. PHP 7.0+ required elsewhere

== Authentication ==

The `/auth` endpoint is the entry point for generating JWTs from WordPress credentials.

**Generate a JWT:**

Make a POST request to `{your-site}/wp-json/simple-jwt-login/v1/auth` with your WordPress email (or username) and password. The response includes both a short-lived JWT and a long-lived refresh token:

``
{
    "success": true,
    "data": {
        "jwt": "GENERATED_JWT_HERE",
        "refresh_token": "GENERATED_REFRESH_TOKEN_HERE"
    }
}
``

**Refresh a JWT without re-authentication:**

POST to `{your-site}/wp-json/simple-jwt-login/v1/auth/refresh` with your `refresh_token`. A new JWT and a new refresh token are returned automatically.

**Validate a JWT:**

GET or POST to `/auth/validate` to check whether a JWT is still valid and retrieve details about the associated WordPress user.

**Revoke a JWT:**

POST to `/auth/revoke` with the `jwt` parameter. The token is immediately invalidated.

The plugin auto-generates sample URLs in the admin for each of these scenarios so you can test without writing code.

[Read more](https://simplejwtlogin.com/docs/authentication/) on our website.

== Auto-Login ==

Auto-login lets any external app, website, or mobile client log in a WordPress user just by embedding a valid JWT in the request - no password exchange needed on the WordPress side.

**How to send the JWT:**

1. URL parameter: `?jwt=YOUR_JWT_HERE` (the parameter name is configurable)
2. Authorization header: `Authorization: Bearer YOUR_JWT_HERE`
3. Cookie: `$_COOKIE['simple-jwt-login-token']`
4. PHP Session: `$_SESSION['simple-jwt-login-token']`

If a JWT appears in multiple locations, the header takes precedence.

**After login, redirect users to:**

* The WordPress dashboard
* The site homepage
* Any custom URL you define

You can also pass a `redirectUrl` query parameter at runtime to override the configured destination, if the "Allow redirect to a specific URL" option is enabled.

**Dynamic URL variables for redirect targets:**

Build personalized landing pages or dashboard links using these variables - they are replaced with real values before the redirect fires:

* `{{site_url}}` - site base URL
* `{{user_id}}` - logged-in user's WordPress ID
* `{{user_email}}` - logged-in user's email address
* `{{user_login}}` - logged-in username
* `{{user_first_name}}` - user's first name
* `{{user_last_name}}` - user's last name
* `{{user_nicename}}` - URL-friendly user slug

Example: `https://yourdomain.com/dashboard?uid={{user_id}}&ref={{user_login}}`

**Security options:**

* Restrict auto-login to specific client IP addresses
* Require an Auth Code alongside the JWT

[Read more](https://simplejwtlogin.com/docs/autologin/) on our website.

== Register Users ==

This feature is disabled by default. When enabled, external clients can create new WordPress users via the REST API without needing an admin session.

**Basic usage:**

POST to `{your-site}/wp-json/simple-jwt-login/v1/users` with at least `email` and `password`. The new user is created and a JSON response is returned.

**Optional registration parameters:**

- **email** (required) - user email address
- **password** (required) - plain-text password (omit if "Generate random password" is enabled)
- **user_login** - username
- **user_nicename** - URL-friendly username
- **user_url** - user website URL
- **display_name** - display name shown publicly
- **nickname** - user nickname
- **first_name** - first name
- **last_name** - last name
- **description** - biographical description
- **locale** - user locale
- **user_registered** - registration date in `Y-m-d H:i:s` format

**Custom user meta on registration:**

Pass a `user_meta` parameter with a JSON object. Every key-value pair is saved as WordPress user meta for the new account:

``
{
    "meta_key": "meta_value",
    "subscription_tier": "free"
}
``

**Useful configuration options:**

* Set the default WordPress role for newly registered users (subscriber, editor, author, contributor, etc.)
* Generate a random password automatically - `password` parameter becomes optional
* Trigger auto-login flow immediately after registration completes
* Limit registrations to specific IP addresses
* Limit registrations to specific email domains (e.g. allow only `@yourcompany.com`)
* Override the default user role per Auth Code, allowing multiple registration tiers from one endpoint

[Read more](https://simplejwtlogin.com/docs/register-user/) on our website.

== API Keys ==

API keys provide an alternative credential for clients that need to obtain JWTs without sending a WordPress password on every request.

Each key is scoped to a specific client and can be revoked independently without affecting other integrations. This is useful for server-to-server integrations, CI pipelines, and any scenario where long-lived credentials are needed but passwords are unsuitable.

Manage your API keys from the **API Keys** tab in the plugin settings. The admin shows a paginated, searchable list of all issued keys along with their creation date and status.

== Audit Logs ==

The Audit Logs tab provides a complete, timestamped record of every login, registration, and authentication event handled by the plugin.

Use audit logs to:

* Debug failed authentication attempts
* Monitor unusual login patterns
* Satisfy compliance requirements that demand an access record
* Trace which client or JWT triggered a specific event

The logs view is paginated and filterable directly from the WordPress admin.

== Delete User ==

This feature is disabled by default.

When enabled, a DELETE request to the users endpoint with a valid JWT will remove the associated WordPress account.

**Configuration:**

* Choose whether to identify the user by **email address** or **WordPress user ID**
* Set the JWT payload key where that identifier is stored
* Restrict deletions to specific IP addresses for an extra layer of protection

[Read more](https://simplejwtlogin.com/docs/delete-user/) on our website.

== Reset Password ==

Both the reset password and change password endpoints are disabled by default.

**Reset password flow:**

1. POST to `/user/reset_password` with the user's email. The plugin sends a reset code to that address.
2. POST to `/user/change_password` with the reset code and the new password to complete the change.

**Customization options:**

* Customize the reset email subject and body from the plugin settings
* Override the email template entirely via the `simple_jwt_login_reset_password_custom_email_template` filter hook
* Skip sending the email altogether and handle delivery yourself via the same hook

[Read more](https://simplejwtlogin.com/docs/reset-password/) on our website.

== Auth Codes ==

Auth codes are optional access tokens that add a layer of protection to any plugin endpoint. They are independent of the JWT and are checked before the JWT is validated.

Enable auth code requirements separately for: auto-login, user registration, and user deletion.

Each auth code has three properties:

1. **Authentication Key** - the value the client must include in the request
2. **WordPress User Role** - optional override for the role assigned when creating users via this code. If blank, the default from Register Settings is used.
3. **Expiration Date** - optional. Format: `YYYY-MM-DD HH:mm:ss`. Leave blank for a non-expiring code.

Auth codes are also how you support multiple user tiers from a single registration endpoint: create one code per role, and the correct role is applied based on which code the client sends.

[Read more](https://simplejwtlogin.com/docs/auth-codes/) on our website.

== Protect Endpoints ==

When enabled, this feature gates WordPress REST endpoints behind JWT authentication so only clients with a valid token can access them.

**Two protection modes:**

* **Apply on All REST Endpoints** - protects every route and lets you whitelist exceptions (e.g. add `wp/v2/posts` to allow public read access to posts while protecting everything else)
* **Apply on Specific Endpoints** - protects only the routes you explicitly list

**Per-endpoint HTTP method filtering:**

You can restrict protection to specific HTTP methods on each endpoint. For example, protect POST and DELETE on `wp/v2/posts` while leaving GET public.

**Endpoint matching options:**

* Exact match
* Starts-with match (useful for protecting entire namespaces)

When a request reaches a protected endpoint without a valid JWT, the plugin returns:

``
{
    "success": false,
    "data": {
        "message": "Your are not authorized to access this endpoint.",
        "error_code": 403,
        "type": "simple-jwt-login-route-protect"
    }
}
``

[Read more](https://simplejwtlogin.com/docs/protect-endpoints/) on our website.

== Webhooks ==

Webhooks let you notify external services in real time when authentication events occur in WordPress.

Configure one or more webhook URLs for each of these events:

* User login
* User registration
* JWT authentication

The plugin fires an HTTP request to each configured URL with event data as the payload. Use webhooks to sync users to a CRM, trigger downstream workflows, or pipe events to a logging service.

The **Webhook Logs** tab in admin shows the result of each outbound call so you can verify delivery and debug failures.

== Hooks ==

The plugin exposes action and filter hooks so developers can extend or customize behavior without modifying plugin files.

**Action hooks:**

* `simple_jwt_login_login_hook(WP_User $user)` - fires after a user logs in via JWT
* `simple_jwt_login_redirect_hook(string $url, array $request)` - fires before the post-login redirect, allowing URL modification
* `simple_jwt_login_register_hook(WP_User $user, string $plain_text_password)` - fires after a new user is registered
* `simple_jwt_login_delete_user_hook(WP_User $user)` - fires immediately after a user is deleted
* `simple_jwt_login_before_endpoint` - fires before any plugin route is initialized

**Filter hooks:**

* `simple_jwt_login_jwt_payload_auth(array $payload, array $request)` - modify JWT payload on `/auth` endpoint before the token is signed
* `simple_jwt_login_no_redirect_message(array $payload, array $request)` - customize the JSON response on `/autologin` when "No Redirect" mode is active
* `simple_jwt_login_reset_password_custom_email_template(string $template, array $request)` - replace or modify the reset password email body

**Example: Send a welcome email on registration**

``
add_action('simple_jwt_login_register_hook', function ($user, $password) {
    wp_mail(
        $user->user_email,
        'Welcome',
        'Your account is ready. Email: ' . $user->user_email . ' / Password: ' . $password
    );
}, 10, 2);
``

**Example: Add user data to the no-redirect autologin response**

``
add_filter('simple_jwt_login_no_redirect_message', function ($response, $request) {
    $response['userId']      = get_current_user_id();
    $response['userDetails'] = wp_get_current_user();
    return $response;
}, 10, 2);
``

**Example: Modify the reset password email template**

``
add_filter('simple_jwt_login_reset_password_custom_email_template', function ($template, $request) {
    return $template . ' - Sent by Acme Corp';
}, 10, 2);
``

View the full list of hooks at [https://simplejwtlogin.com/docs/hooks](https://simplejwtlogin.com/docs/hooks).

== CORS ==

Configure Cross-Origin Resource Sharing (CORS) headers for all plugin REST routes from the CORS tab.

This is essential when your headless frontend (Next.js, React, Vue, etc.) is hosted on a different domain than your WordPress backend. Without correct CORS headers, browser-based clients are blocked from calling the plugin's endpoints.

The plugin lets you configure allowed origins, methods, and headers without touching server configuration files.

[Read more](https://simplejwtlogin.com/docs/cors/) on our website.

== Integration ==

**PHP SDK**

A Composer package is available for PHP applications:

``
composer require nicumicle/simple-jwt-login-client-php
``

See the [package page](https://packagist.org/packages/nicumicle/simple-jwt-login-client-php) for code examples.

**JavaScript SDK**

An npm/yarn package is available for JavaScript and TypeScript applications:

``
npm install "simple-jwt-login"
``

or

``
yarn add "simple-jwt-login"
``

See the [JavaScript SDK repository](https://github.com/simple-jwt-login/js-sdk) for full documentation.

**CLI**

A command-line tool is available for scripting, testing, and local development workflows:

``
# Install and use the CLI to interact with all plugin endpoints from your terminal
``

See the [CLI repository](https://github.com/simple-jwt-login/simple-jwt-login-cli) for installation instructions.

== Screenshots ==

1. Dashboard - overview of routes, security, configuration, and monitoring sections with status cards
2. General - JWT algorithm, verification key, user identification, JWT input sources, and security options
3. Login - auto-login toggle, redirect behavior after login/failure, and access control by IP and JWT user
4. Register - user registration endpoint, role assignment, random password generation, post-registration options, and user meta keys
5. Delete - delete user endpoint, authentication code requirement, and IP access control
6. Reset Password - password reset endpoint, reset flow options, and email customization with template variables
7. Authenticate - JWT generation endpoint, authentication options, header configuration, payload claims, and token expiration
8. Refresh Token - refresh token endpoint, token lifetime window, and secret key configuration
9. Validate Token - validate token endpoint and authentication code requirement
10. Revoke Token - revoke token endpoint and authentication code requirement
11. Auth Codes - authorization codes list with per-code WordPress role and expiration date
12. Protect Endpoints - JWT protection scope selector and whitelisted endpoint rules
13. CORS - CORS support toggle and header configuration (Allow-Origin, Allow-Methods, Allow-Headers)
14. API Keys - API key management with create form, permissions, expiry, and existing keys table
15. OAuth - login page button layout and OAuth provider configuration (Google, Auth0, Facebook, GitHub)
16. Third-Party Integrations - WPGraphQL, Two-Factor, and Force Login plugin integrations
17. Webhooks Config - outbound webhook configuration with HTTP method, event filters, and endpoint URL
18. Audit Logs Config - audit logging events selection and log retention period settings
19. Hooks - WordPress action and filter hooks exposed by the plugin with parameters and descriptions
20. JWT Decoder - in-browser JWT decoder tool to inspect token header and payload
21. Login page - WordPress login page with OAuth provider buttons (Google, Auth0, Facebook, GitHub)
22. Try it out - built-in endpoint tester with parameter fields, live request URL, and cURL/JS/PHP code snippets

== Installation ==

**From the WordPress plugin directory (recommended for production)**

1. Go to Plugins > Add New in your WordPress admin.
2. Search for "Simple JWT Login".
3. Click Install Now, then Activate.

**From a zip file**

1. Download the plugin zip file.
2. Go to Plugins > Add New > Upload Plugin.
3. Upload the zip and click Install Now, then Activate.

**Initial setup**

After activation, go to Settings > Simple JWT Login:

1. Open the **General** tab.
   - Set a strong **JWT Decryption Key**. This secret is used to sign and validate all tokens - keep it private.
   - Select the **JWT Decryption Algorithm** (HS256 is a safe default; RS256 is recommended for production systems that need asymmetric keys).

2. Open the **Login** tab.
   - Set **Allow Auto-login** to Yes.
   - Set the **JWT parameter key** - this is the key inside your JWT payload that contains the WordPress user email or user ID.

3. Save Changes.

You can now copy the sample URL from the top of the Login tab, replace the placeholder JWT with a real token, and test auto-login in your browser.

**Adding the JWT to requests:**

Via URL parameter:

``
https://yoursite.com?jwt=YOUR_JWT_HERE
``

Via Authorization header (recommended for API clients):

``
Authorization: Bearer YOUR_JWT_HERE
``

The header takes precedence over the URL parameter if both are present.

== Frequently Asked Questions ==

= Is this plugin secure? =

Yes. The plugin validates every JWT against your configured decryption key and algorithm before taking any action. No action is performed on an invalid or expired token.

For production use: choose a long, random JWT decryption key; enable auth codes on sensitive endpoints; restrict operations to known IP addresses where practical; and review the audit logs regularly.

= Can I disable the register or delete user endpoints? =

Yes. Every endpoint is disabled by default. You enable only what you need from the plugin settings.

= Can I limit which email addresses can register? =

Yes. Add one or more email domains (e.g. `yourcompany.com`) to the domain allowlist in Register Settings. Users attempting to register with any other domain will receive an error.

= Can I use a JWT issued by another plugin or service? =

Yes. Any JWT signed with the same key and algorithm that you configure in the plugin will be accepted. This is how you share authentication between two separate services.

= Is the Auth Code required? =

No. Auth codes are optional and disabled by default. You can enable or disable the requirement independently for auto-login, user registration, and user deletion.

= How do I create users with different roles from the same endpoint? =

Create one Auth Code per role in the Auth Codes tab and assign the desired WordPress role to each code. When the client sends a specific Auth Code with the registration request, the matching role is applied instead of the default.

= Can I log in from a mobile app using this plugin? =

Yes. Auto-login via JWT is the primary use case. Your mobile app (React Native, Flutter, native iOS/Android, etc.) obtains a JWT from the `/auth` endpoint and then uses it on any subsequent request.

= Where does the plugin look for the JWT? =

In this order: URL parameter, Cookie (`$_COOKIE['simple-jwt-login-token']`), Session (`$_SESSION['simple-jwt-login-token']`), Authorization header. The parameter name for each source is configurable in the General settings.

= Does this work with WPGraphQL? =

Yes. Enable the WPGraphQL integration from the Applications tab. Once enabled, authenticated GraphQL queries will work using the same JWT credentials.

= I got a "403 not authorized" response on a protected endpoint. =

Make sure you are passing a valid, non-expired JWT either as a Bearer token in the Authorization header or via the configured URL parameter. You can use the `/auth/validate` endpoint to check whether a specific JWT is still valid before sending it to a protected route.

== Changelog ==

A complete changelog is available on the [GitHub repository](https://github.com/nicumicle/simple-jwt-login/blob/master/Changelog.md).

= 4.0.0 =
- New UI for better a user experience
- New: API Keys management - issue and revoke per-client keys from the admin
- New: Audit Logs - full paginated event history for login, register, and authentication events
- New: Facebook OAuth social login
- New: GitHub OAuth social login
- New: WPGraphQL integration - authenticate GraphQL queries using JWT
- New: HTTP method filtering on protected endpoints

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
- Switch `get_user_by_email` to `get_user_by()` due to [deprecation](https://developer.wordpress.org/reference/functions/get_user_by_email/)
- Remove method `convertUserToArray` from WordPressData.
- Drop support for PHP 5.3 and PHP 5.4

= 3.4.10 (14 Dec 2022) =
- Fix issue with rest routes

= 3.4.9 (04 Dec 2022) =
- Add Strength indicator for JWT decryption key
- Allow setting custom length for random password. The default is 10 characters.
- Allow sending base_64 encoded `password` and `passhash` on the `/auth` endpoint
- Fix issue with `includeRequestParameters` that has been building incorrect URLs
- Add query parameters filter on autologin redirect
- Add the `simple_jwt_login_before_endpoint` hook before all simple-jwt-login routes are initialized

= 3.4.8 (04 Nov 2022) =
- Add filter to allow the change for authentication payload
- Change how we log in the user on while using the "protect endpoint" feature
- Refactor Route Service getUserFromJWT method
- Sanitize data from request
- Fix password issue when it contains special characters
- `/auth/validate` endpoint supports both `GET` and `POST` methods

= 3.4.7 (02 Oct 2022) =
- Remove unused code from the JWT library
- Move JWT Library to a folder
- Stay on current page after saving settings
- Some small text sanitizations
- Change how views are loaded in order to prevent "local file inclusion risk"

= 3.4.6 (27 Apr 2022) =
- Fix user_meta when passed as json in request body

= 3.4.5 (11 Apr 2022) =
- Add Redirect on Fail autologin
- Add shortcodes for displaying autologin errors

= 3.4.4 (03 Apr 2022) =
- Add hooks for all success responses

= 3.4.3 ( 30 Jan 2022) = 
- Tested with WordPress 5.9
- Do not add empty JWT to Authorization header

= 3.4.2 (14 Dec 2021) =
- Display user roles on auth/validate and on register user

= 3.4.1 (05 Dec 2021) =
- Fix protect endpoint conflict with wp-admin actions
- Check if user role exists
- Improve logic for protect endpoints
- Allow Authentication with DB hashed password
- Change user password with JWT

= 3.4.0 (17 Oct 2021) =
- Implement protected endpoints

= 3.3.1 (13 Oct 2021) =
- Sanitize load views

= 3.3.0 (13 Oct 2021) =
- Sanitize all displayed texts
- Add missing translation texts
- Update bootstrap libraries
- Update all translations
- Improve random password algorithm for better security

= 3.2.1 (09 Oct 2021) =
- Fix CSRF for admin settings

= 3.2.0 (26 Sept 2021) =
- Add user to simple_jwt_login_register_hook and simple_jwt_login_login_hook hooks
- Add option to allow adding a JWT in the register user endpoint

= 3.1.0 (31 July 2021) =
- Add reset password and change password endpoints

= 3.0.0 (11 July 2021) =
- Plugin code refactor
- Rewrite file autoloader
- Improve parse request parameters
- Add support for JSON body requests
- Fix user_meta URL encoded
- Add support for Force Login plugin
- Add Auth codes to dashboard
- Add IP limitation for Authentication
- Add support for Delete user by username
- Add support for `*` in IP restrictions
- Fix user role `None` when empty role in Auth Codes
- Add Auth code on Authentication endpoint

= 2.6.2 (29 April 2021) =
- Update documentation link with plugin website URL

= 2.6.1 (10 April 2021) =
- Add documentation link

= 2.6.0 (08 December 2020) =
- Add `No Redirect` option for autologin and respond with a json on this endpoint
- Add Hook for `No redirect` in order to customize the autologin response

= 2.5.2 (27 November 2020) =
- Add permission callback to api routes
- Use session start only when session token has been activated

= 2.5.1 (16 November 2020) =
- Fix Authorization header

= 2.5.0 (15 November 2020) =
- Add key change for URL, Session, Cookie and Header parameters

= 2.4.1 (21 October 2020) =
- Add more variables for `redirectUrl`

= 2.4.0 (20 October 2020) =
- Add `redirectUrl` parameter
- Add variables for URLs
- Fix session start warning

= 2.3.1 (01 September 2020) =
- Highlight Settings errors and display section
- fix PHP warning for session_start()

= 2.3.0 (25 August 2020) =
- Add support for revoke token: /auth/revoke
- Allow adding extra parameters in payload on /auth endpoint
- Add filter on /auth in order to allow payload modification
- Add support for user_meta on create user
- Allow users to set decryption key in WordPress PHP code
- Display number of active hooks on dashboard
- Improve error system from plugin settings

= 2.2.7 (05 August 2020) =
- Fix warning for "register_rest_route was called incorrectly"
- Fix getting JWT from header: ignore white spaces
- Allow users to store base64 encoded decryption keys and use them as decoded when needed

= 2.2.6 (20 July 2020) =
* Fix issue with saving JWT algorithm
* Allow usage of certificates in order to encode/decode JWT
* Add option to add username in JWT payload
* Users can authenticate with WordPress username for /auth endpoint

= 2.2.5 (18 July 2020) =
* Allow login by username ( user_login )
* beta: Allow users to access private endpoints via API with JWT

= 2.2.4 (13 July 2020) =
* Fix tabs visibility issue on some WordPress versions

= 2.2.3 (10 July 2020) =
* Add a toggle for all hooks
* Fix CORS issue

= 2.2.2 (09 July 2020) =
* Attach plugin version to js and css
* Change the path for js and css files
* Change the load order for the JS files

= 2.2.1 (08 July 2020) =
* Fix issue with bootstrap

= 2.2.0 (29 June 2020) =
* Add /auth/validate endpoint to validate tokens and get some details about the user that it is present in the JWT

= 2.1.1 (26 June 2020) =
* Fix error for auto-login after registering user

= 2.1.0 (20 June 2020) =
* Add support for CORS
* Include request parameters used for login link in the REDIRECT URL
* Add initial request data to the hook simple_jwt_login_redirect_hook call
* Add expiration date and user role to AUTH Codes

= 2.0.0 (14 June 2020) =
* New UI for plugin configuration
* Allow users to enable/disable specific hooks
* Add route for JWT generator
* Add route that refreshes an expired JWT
* Allow custom user_login for new users.
* Add WP_user in create user response

= 1.6.4 (06 June 2020) =
* Fix route PHP warning

= 1.6.3 (26 May 2020) =
* Add a hook that is called before the user it is redirected to the page he specified in the login section.

= 1.6.2 (23 May 2020) =
* Add plain text password to register user hook
* Update documentation
* Add option for a random password on new created users
* Add option 'Initialize force login after register' - that allows users to continue on the auto-login flow after user registration
* Add more options for create new user
* Add more options when a new user is created

= 1.6.1 (20 May 2020) =
* Improve mechanism for detecting if plugin needs update/create for DB option
* Add new option to get JWT from '$_COOKIE' and '$_SESSION'
* Update readme

= 1.6.0 (17 May 2020) =
* Fix save settings with minimum number of parameters ( No auth codes if all options are disabled)
* Add hooks for login, register and create User.
* Ignore case for JWT parameter
* JWT can be added in header
* Update Readme

= 1.5.0 (05 Feb 2020) =
* Allow to delete users based on a JWT token
* Refactor routes section
* Allow users to set custom namespace for API route
* Change create user route name and offer support for backward compatibility

= 1.4.0 (29 Jan 2020) =
* Add codes to errors
* Code refactor
* Allow save in settings with no AUTH_KEYS when they are not used
* Improve sample URL generators
* Small UI Changes
* Fix validations
* Keep settings values even if there is an error
* Update readme

= 1.3.1 (20 Dec 2019) =
* Plugin can be configured only by administrators

= 1.3.0 (28 Nov 2019) =
* Add support for translations
* Code refactor

= 1.2.4 (26 Nov 2019) =
* Improve UI for Auth codes
* Update Readme

= 1.2.3 (16 Nov 2019) =
* Allow users to change Auth Key parameter

= 1.2.2 (16 Nov 2019) =
* Add support for getting key from jwt and array

= 1.2.1 (23 June 2019) =
* Add functionality for copy login and register example URL

= 1.2.0 (21 June 2019) =
* Allow login by email or WordPress user ID
* UI / UX small improvements

= 1.1.0 (15 June 2019) =
* Add support for IP address limitation for login / register
* Allow users to register only with emails from specific domains
* Possibility to make requests without Auth Codes

= 1.0.0 (14 June 2019) =
* Initial release
