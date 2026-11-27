# Security Policy

## Supported versions

| Version | Supported | Security patches until |
|---------|-----------|------------------------|
| 4.x | Yes (Active, LTS) | - |
| 3.x | Security and WordPress compatibility fixes only | 31.01.2027 |
| < 3.0 | No | - |

Security fixes are released as PATCH versions (for example `4.2.1`) and published on
[WordPress.org](https://wordpress.org/plugins/simple-jwt-login/). Always run the latest 4.x release.
See the [Version Support Policy](README.md#calendar-version-support-policy) for details.

## Reporting a vulnerability

**Please do not report security issues in public GitHub issues, pull requests, discussions or the
WordPress.org support forum.**

Report them privately on GitHub: [Report a vulnerability](https://github.com/nicumicle/simple-jwt-login/security/advisories/new)
(GitHub private vulnerability reporting).

Please include:

- the affected plugin version and, if known, the affected file / endpoint;
- the plugin settings needed to reproduce (export them from the plugin's admin, with keys and secrets removed);
- other plugins involved (e.g. a two-factor or Force Login plugin) and any custom hook callbacks;
- step-by-step reproduction (HTTP requests are ideal) and the expected vs. actual result;
- the impact you observed, and whether you tested it on a real installation or in a code harness.

Only test against WordPress installations you own. Never access or change other users' data.

## What happens next

- We confirm the report and ask follow-up questions if needed.
- Confirmed issues are fixed in a PATCH release. Every fix ships with a regression test that
  reproduces the original issue.
- We coordinate the disclosure date with you. Details are published only after a fixed version
  is available on WordPress.org.
- With your permission, you are credited in the GitHub security advisory.

## Scope

In scope: the Simple JWT Login plugin code in this repository (`simple-jwt-login/`), its REST
endpoints, admin screens, integrations (OAuth providers, two-factor, WooCommerce, WPGraphQL,
Force Login, AI abilities / MCP) and the generated JWTs.

Usually out of scope:

- issues that need administrator access (`manage_options`) to exploit, unless they cross a
  boundary the plugin promises to keep (for example AI agents changing human-only settings or
  reading secrets);
- behaviour of options that the admin screen and the dashboard's security notices already flag
  as insecure, while they are enabled;
- vulnerabilities in WordPress core, other plugins or the server setup;
- denial of service by request volume, missing rate limiting without a concrete impact, and
  social engineering.

If you are unsure whether something is in scope, report it anyway.
