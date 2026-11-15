# Cross-site tests (CSRF + CORS)

Calls from **another site** to the WordPress site, with [Playwright Test](https://playwright.dev):

- `specs/server-calls.spec.mjs` - raw HTTP calls with curl from the other site's container to the
  WordPress container (any header combination a server can send).
- `specs/browser-calls.spec.mjs` - pages of the other site (`other-site/`) opened in a real Chrome
  whose user is logged in to WordPress (JWT cookie), for CSRF and CORS, plus a control (the same
  form from WordPress itself must work).
- `specs/endpoints.spec.mjs` - the plugin's endpoints (auth, refresh, register, validate, revoke,
  delete user, reset password, autologin) and WordPress core (`/wp/v2/users/me`, with and without
  a JWT), called from the other site's pages: as a cross-origin app (CORS) and as CSRF (form or
  link, the browser adding the JWT cookie). Checks the effect and the HTTP status.
- `specs/cors.spec.mjs` - the CORS settings: exact response headers for `*`, one origin, several
  origins (comma / new line), lookalikes, preflight methods and headers; and what a real page on
  three origins (`127.0.0.1:8090`, `127.0.0.1:8091`, `localhost:8090`) may read.

Each case tries to have an effect (create an administrator, delete a user, revoke a JWT, log in,
read an answer...). `BLOCKED` = no effect, `WORKS` = the effect happened. The "works" cases prove that the "blocked" ones are blocked by the plugin, not by the setup.

## Run locally

Needs docker, Node 22+ and Google Chrome.

```bash
(cd docker && docker compose up -d)   # WordPress on :88, the other site on :8090 and :8091
cd tests/CrossSite
npm ci
npx playwright test                   # all cases
npx playwright test -g "CORS allows"  # only some
npx playwright show-report            # HTML report: per case, what the browser really sent
```

`http://127.0.0.1:8090` is cross-site to `http://localhost:88`, `http://localhost:8090` is
same-site (same host, other port).

The suite replaces the plugin settings and restores them at the end. **Never run it together with
the feature tests**: both rewrite the same WordPress option.

## Add a case

- **Any REST call from the other site:** add a row to `cases` in `specs/endpoints.spec.mjs`. The
  generic page `other-site/call.html` makes the call described in the row (`mode` cors / form /
  navigate, `cred` none / header / cookie):
  ```js
  { name: 'WP /posts: app on the listed origin', site: OTHER,
    call: { route: '/wp/v2/posts', method: 'GET', mode: 'cors', cred: 'header' },
    effect: answered(200), expect: WORKS, status: 200 },
  ```
  `effect` says whether the call did something (user deleted, logged in, answer readable...).
- **CORS:** a row in `headerCases` (exact headers, any origin) or in `browserGroups` (real page,
  readable or not) in `specs/cors.spec.mjs`.
- **Browser:** add a row to a group in `specs/browser-calls.spec.mjs`, or a new group with its
  own CORS settings (`CORS_OFF`, `cors('<origin>')`, `cors('*')`):
  ```js
  { site: OTHER, page: 'form.html', cookie: 'None', expect: BLOCKED, cookieSent: true },
  ```
  For a new kind of call, add a page to `other-site/` that uses `common.js` (`target`,
  `newAdmin`, the JWT in the `#jwt=` fragment) and writes its result to `document.title` when it
  can read the answer.
- **Server call:** add a row to `cases` in `specs/server-calls.spec.mjs`:
  ```js
  { name: 'cookie, null Origin', headers: ['Origin: null', cookie], expect: BLOCKED },
  ```
- **Other settings:** `applySettings()` in `support/wordpress.mjs` builds them from the unit test
  fixture; add what you need there.

## Configuration

Environment variables (defaults match `docker/docker-compose.yaml`, CI sets its own):

| Variable | Default | |
|---|---|---|
| `WP_EXEC` | `docker exec -i wordpress` | command prefix that runs WP-CLI |
| `WP_PATH` | `/var/www/html` | WordPress path for WP-CLI |
| `OTHER_SITE_EXEC` | `docker exec sjl-other-site` | command prefix that runs curl on the other site |
| `TARGET` | `http://localhost:88` | WordPress, as the browser sees it |
| `TARGET_INTERNAL` | `http://wordpress` | WordPress, as the other site's container sees it |
| `OTHER_SITE` | `http://127.0.0.1:8090` | the other site (cross-site) |
| `OTHER_SITE_SAME_HOST` | `http://localhost:8090` | the other site on WordPress's host (same-site) |
| `SECOND_SITE` | `http://127.0.0.1:8091` | the other site's pages on a second origin |
| `PW_CHANNEL` | `chrome` | `chromium` uses Playwright's browser (`npx playwright install chromium`) |

Chrome keeps `SameSite=None` (Secure) cookies only for https or `localhost`, so `TARGET` must be
one of them.
