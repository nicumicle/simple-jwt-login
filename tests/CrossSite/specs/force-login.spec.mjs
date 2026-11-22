// Anonymous Force Login bypass: before 4.1.0 the Force Login integration matched the raw
// REQUEST_URI against the plugin namespace, so a duplicated `rest_route` (plugin route first,
// core route last) let an anonymous visitor read core routes that Force Login blocks.
// tests/Feature/fixtures/sjl-test-force-login.php plays the Force Login plugin: it is installed
// as a mu-plugin for this file and only acts on calls carrying the X-Sjl-Test-Force-Login header.
//
// To add a case: add a row. `blocked` = Force Login's 401 `rest_unauthorized` must stay.
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { config } from '../support/config.mjs';
import { expect, test } from '../support/fixtures.mjs';
import { applySettings, CORS_OFF, installMuPlugin, otherSiteCurl, removeMuPlugin, wpCli } from '../support/wordpress.mjs';

const FORCE_LOGIN = 'sjl-test-force-login.php';
const fixture = join(dirname(fileURLToPath(import.meta.url)), '../../Feature/fixtures', FORCE_LOGIN);

const cases = [
  { name: 'baseline core route', query: 'rest_route=/wp/v2/users', blocked: true },
  {
    name: 'duplicated rest_route, plugin auth route first',
    query: 'rest_route=/simple-jwt-login/v1/auth&rest_route=/wp/v2/users',
    blocked: true,
  },
  {
    name: 'duplicated rest_route, plugin namespace first',
    query: 'rest_route=/simple-jwt-login/v1/&rest_route=/wp/v2/users',
    blocked: true,
  },
  { name: 'control: plugin route stays reachable', query: 'rest_route=/simple-jwt-login/v1/auth', blocked: false },
];

test.describe('Force Login integration, anonymous calls from the other site\'s container', () => {
  let siteHost;

  test.beforeAll(() => {
    installMuPlugin(fixture, FORCE_LOGIN);
    applySettings(CORS_OFF, { integrations: { '3rdparty': { force_login: { enabled: true } } } });
    // The pre-4.1.0 check compared HTTP_HOST + REQUEST_URI with the site URL, so send the real Host.
    siteHost = new URL(wpCli('option', 'get', 'siteurl')).host;
  });

  test.afterAll(() => {
    removeMuPlugin(FORCE_LOGIN);
    applySettings(CORS_OFF);
  });

  for (const testCase of cases) {
    test(`${testCase.blocked ? 'blocked' : 'works'}: ${testCase.name}`, () => {
      const output = otherSiteCurl('-s', '-w', '\n%{http_code}', `${config.targetInternal}/?${testCase.query}`,
        '-H', 'X-Sjl-Test-Force-Login: 1', '-H', `Host: ${siteHost}`);
      const lines = output.split('\n');
      const status = lines.pop();
      const body = lines.join('\n');
      let code = null;
      try {
        code = JSON.parse(body)?.code ?? null;
      } catch {
        // not JSON
      }

      if (!testCase.blocked) {
        expect(code, body.slice(0, 200)).not.toBe('rest_unauthorized');
        return;
      }
      expect(status, body.slice(0, 200)).toBe('401');
      expect(code).toBe('rest_unauthorized');
    });
  }
});
