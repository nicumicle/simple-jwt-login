// Raw HTTP calls sent with curl from the other site's container to the WordPress container.
// A server can send any header, so these check the plugin's decision on each header combination.
//
// To add a case: add a row. `headers` get `{jwt}` and `{otherSite}` replaced.
import { config } from '../support/config.mjs';
import { BLOCKED, expect, test, WORKS } from '../support/fixtures.mjs';
import { applySettings, CORS_OFF, otherSiteCurl } from '../support/wordpress.mjs';

const cookie = `Cookie: ${config.cookieName}={jwt}`;

const cases = [
  {
    name: 'Patchstack PoC: cookie, other Origin + Referer',
    headers: ['Origin: http://attacker.example', 'Referer: http://attacker.example/csrf.html', cookie],
    expect: BLOCKED,
  },
  { name: 'cookie, Origin = the other site', headers: ['Origin: {otherSite}', cookie], expect: BLOCKED },
  {
    name: 'Authorization header, Origin = the other site',
    headers: ['Origin: {otherSite}', 'Authorization: Bearer {jwt}'],
    expect: BLOCKED,
  },
  {
    name: 'cookie, sibling subdomain (Sec-Fetch-Site same-site, no Origin)',
    headers: ['Sec-Fetch-Site: same-site', cookie],
    expect: BLOCKED,
  },
  { name: 'server-to-server call (no browser headers)', headers: [cookie], expect: WORKS },
  { name: 'server-to-server call, Authorization header', headers: ['Authorization: Bearer {jwt}'], expect: WORKS },
];

test.describe('server calls from the other site\'s container (CORS off)', () => {
  test.beforeAll(() => applySettings(CORS_OFF));

  for (const testCase of cases) {
    test(`${testCase.expect}: ${testCase.name}`, async ({ marker, jwt, expectOutcome }) => {
      const headerArgs = testCase.headers.flatMap((header) => [
        '-H', header.replaceAll('{jwt}', jwt).replaceAll('{otherSite}', config.otherSite),
      ]);
      const status = otherSiteCurl('-s', '-o', '/dev/null', '-w', '%{http_code}', '-X', 'POST',
        `${config.targetInternal}/?rest_route=/wp/v2/users`,
        '-H', 'Content-Type: application/x-www-form-urlencoded', ...headerArgs,
        '--data', `username=${marker}&email=${marker}%40example.invalid&roles=administrator&password=Xk9qz_server_pw`);

      expect(status, 'WordPress must answer').toMatch(/^\d{3}$/);
      expectOutcome(marker, testCase.expect);
    });
  }
});
