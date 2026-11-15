// Pages of the other site (tests/CrossSite/other-site/) opened in a real Chrome whose user is
// logged in to WordPress (JWT cookie). Each page tries to create an administrator.
//
// To add a case: add a row to a group (or a new group with its own CORS settings).
//   site:       a key of SITES ('other site' = cross-site, 'same host, other port' = same-site)
//   page:       a page in other-site/, with its query (e.g. 'cors.html?cred=header')
//   cookie:     the JWT cookie the browser holds: 'None', 'Lax' or null (no cookie)
//   expect:     BLOCKED (no administrator) or WORKS (administrator created)
//   cookieSent: optional, asserts whether the browser attached the JWT cookie to the call
//   title:      optional, the page's result (cors.html: 'readable HTTP <status>' or 'not readable: ...')
import { config } from '../support/config.mjs';
import { BLOCKED, expect, otherSitePage, SITES, test, WORKS } from '../support/fixtures.mjs';
import { applySettings, cors, CORS_OFF } from '../support/wordpress.mjs';

const OTHER = 'other site';
const SAME_HOST = 'same host, other port';

// CSRF: every page, from both origins, with both cookie modes.
const csrfCases = ['None', 'Lax'].flatMap((cookie) => ['form.html', 'navigate.html', 'fetch.html'].flatMap((page) => [
  // SameSite=None: the browser attaches the cookie, so only the plugin can stop the call.
  { site: OTHER, page, cookie, expect: BLOCKED, ...(cookie === 'None' ? { cookieSent: true } : {}) },
  { site: SAME_HOST, page, cookie, expect: BLOCKED, cookieSent: true },
]));

const groups = [
  { name: 'CORS off', cors: CORS_OFF, cases: [
    ...csrfCases,
    { site: OTHER, page: 'cors.html?cred=header', cookie: null, expect: BLOCKED },
  ] },
  { name: `CORS allows ${config.otherSite}`, cors: cors(config.otherSite), cases: [
    { site: OTHER, page: 'cors.html?cred=header', cookie: null, expect: WORKS, title: 'readable HTTP 201' },
    { site: OTHER, page: 'cors.html?cred=cookie', cookie: 'None', expect: WORKS, cookieSent: true, title: 'readable HTTP 201' },
    { site: SAME_HOST, page: 'cors.html?cred=header', cookie: null, expect: BLOCKED },
    { site: SAME_HOST, page: 'cors.html?cred=cookie', cookie: 'None', expect: BLOCKED },
  ] },
  { name: 'CORS allows "*"', cors: cors('*'), cases: [
    { site: OTHER, page: 'cors.html?cred=header', cookie: null, expect: WORKS, title: 'readable HTTP 201' },
    // "*" trusts the Authorization header only, never the cookie.
    { site: OTHER, page: 'cors.html?cred=cookie', cookie: 'None', expect: BLOCKED },
    { site: OTHER, page: 'form.html', cookie: 'None', expect: BLOCKED, cookieSent: true },
  ] },
  { name: 'CORS allows another origin (https://app.example)', cors: cors('https://app.example'), cases: [
    { site: OTHER, page: 'cors.html?cred=header', cookie: null, expect: BLOCKED },
    { site: OTHER, page: 'cors.html?cred=cookie', cookie: 'None', expect: BLOCKED },
  ] },
];

for (const group of groups) {
  test.describe(`browser, ${group.name}`, () => {
    test.beforeAll(() => applySettings(group.cors));

    for (const testCase of group.cases) {
      const name = `${testCase.expect}: ${testCase.site} ${testCase.page}, cookie ${testCase.cookie || 'none'}`;
      test(name, async ({ marker, jwt, callFromPage, expectOutcome }) => {
        const call = await callFromPage({
          url: otherSitePage(SITES[testCase.site], testCase.page, marker, jwt),
          cookie: testCase.cookie,
        });

        if (testCase.cookieSent !== undefined) {
          expect(call.cookieSent, 'JWT cookie attached by the browser').toBe(testCase.cookieSent);
        }
        if (testCase.title !== undefined) {
          expect(call.title).toBe(testCase.title);
        }
        expectOutcome(marker, testCase.expect);
      });
    }
  });
}

// Control: the same form submitted from a page of the WordPress site itself must work,
// which proves the blocked cases above are blocked by the plugin, not by the setup.
test.describe('browser, control', () => {
  test.beforeAll(() => applySettings(CORS_OFF));

  test('works: form submitted from the WordPress site, cookie Lax', async ({ marker, callFromPage, expectOutcome }) => {
    const call = await callFromPage({
      url: `${config.target}/?rest_route=/`,
      cookie: 'Lax',
      action: (page) => page.evaluate(({ target, username }) => {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `${target}/?rest_route=/wp/v2/users`;
        const fields = { username, email: `${username}@example.invalid`, roles: 'administrator', password: 'Xk9qz_control_pw' };
        for (const [name, value] of Object.entries(fields)) {
          const input = document.createElement('input');
          input.name = name;
          input.value = value;
          form.appendChild(input);
        }
        document.body.appendChild(form);
        form.submit();
      }, { target: config.target, username: marker }),
    });

    expect(call.cookieSent).toBe(true);
    expectOutcome(marker, WORKS);
  });
});
