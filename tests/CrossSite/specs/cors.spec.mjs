// CORS settings: what WordPress answers to each origin, and what a real browser on another site
// is then allowed to read.
//
// To add a case: add a row to `headerCases` (exact response headers, any origin string) or to a
// group in `browserGroups` (a real page on one of SITES, read allowed or not).
import { config } from '../support/config.mjs';
import { callPage, expect, SITES, test } from '../support/fixtures.mjs';
import { applySettings, cors, CORS_OFF, responseHeaders } from '../support/wordpress.mjs';

const APP = 'https://app.example';
const SHOP = 'https://shop.example';
const READ_ROUTE = '/';

// --- 1. response headers (curl from the other site's container) -------------------------------
//   allowOrigin: expected Access-Control-Allow-Origin (null = header absent)
//   credentials: expected Access-Control-Allow-Credentials (null = absent)
const headerCases = [
  { name: '"*" echoes any origin', cors: cors('*'), origin: 'https://anything.example', allowOrigin: 'https://anything.example', credentials: 'true' },
  { name: '"*" without Origin header', cors: cors('*'), origin: null, allowOrigin: '*', credentials: null },
  { name: 'one origin: listed', cors: cors(APP), origin: APP, allowOrigin: APP, credentials: 'true' },
  { name: 'one origin: not listed', cors: cors(APP), origin: 'https://evil.example', allowOrigin: null, credentials: null },
  { name: 'one origin: other scheme', cors: cors(APP), origin: 'http://app.example', allowOrigin: null, credentials: null },
  { name: 'one origin: other port', cors: cors(APP), origin: 'https://app.example:8443', allowOrigin: null, credentials: null },
  { name: 'one origin: subdomain', cors: cors(APP), origin: 'https://sub.app.example', allowOrigin: null, credentials: null },
  { name: 'one origin: lookalike suffix', cors: cors(APP), origin: 'https://app.example.evil.example', allowOrigin: null, credentials: null },
  { name: 'one origin: Origin null', cors: cors(APP), origin: 'null', allowOrigin: null, credentials: null },
  { name: 'listed with trailing slash and upper case', cors: cors('HTTPS://APP.EXAMPLE/'), origin: APP, allowOrigin: APP, credentials: 'true' },
  { name: 'several origins (comma): first', cors: cors(`${APP}, ${SHOP}`), origin: APP, allowOrigin: APP, credentials: 'true' },
  { name: 'several origins (comma): second', cors: cors(`${APP}, ${SHOP}`), origin: SHOP, allowOrigin: SHOP, credentials: 'true' },
  { name: 'several origins (new line): second', cors: cors(`${APP}\n${SHOP}`), origin: SHOP, allowOrigin: SHOP, credentials: 'true' },
  { name: 'several origins: not listed', cors: cors(`${APP}, ${SHOP}`), origin: 'https://evil.example', allowOrigin: null, credentials: null },
  { name: '"Allow origin" off: WordPress default (echoes the origin)', cors: cors(APP, { allow_origin_enabled: false }), origin: 'https://evil.example', allowOrigin: 'https://evil.example', credentials: 'true' },
  { name: 'CORS off: WordPress default (echoes the origin)', cors: CORS_OFF, origin: 'https://evil.example', allowOrigin: 'https://evil.example', credentials: 'true' },
];

test.describe('CORS response headers', () => {
  for (const testCase of headerCases) {
    test(testCase.name, () => {
      applySettings(testCase.cors);
      const headers = responseHeaders('GET', `/?rest_route=${READ_ROUTE}`, testCase.origin ? [`Origin: ${testCase.origin}`] : []);

      expect(headers['access-control-allow-origin'] || [], 'one Access-Control-Allow-Origin at most')
        .toHaveLength(testCase.allowOrigin === null ? 0 : 1);
      expect(headers['access-control-allow-origin']?.[0] ?? null).toBe(testCase.allowOrigin);
      expect(headers['access-control-allow-credentials']?.[0] ?? null).toBe(testCase.credentials);
    });
  }

  test('preflight answers with the configured methods and headers', () => {
    applySettings(cors(APP, { allow_methods: 'GET, POST, PUT', allow_headers: 'Authorization, X-Custom' }));
    const headers = responseHeaders('OPTIONS', '/?rest_route=/simple-jwt-login/v1/auth', [
      `Origin: ${APP}`, 'Access-Control-Request-Method: PUT', 'Access-Control-Request-Headers: authorization, x-custom',
    ]);

    expect(headers['access-control-allow-origin']).toEqual([APP]);
    expect(headers['access-control-allow-methods']).toEqual(['GET, POST, PUT']);
    expect(headers['access-control-allow-headers']).toEqual(['Authorization, X-Custom']);
  });
});

// --- 2. what a page on another site can read (real Chrome) -------------------------------------
//   site:     a key of SITES
//   call:     the REST call call.html makes (CORS mode)
//   readable: true when the browser must let the page read the answer
const OTHER = 'other site';
const SECOND = 'second site';
const SAME_HOST = 'same host, other port';
const read = { route: READ_ROUTE, method: 'GET', mode: 'cors', cred: 'none' };

const browserGroups = [
  { name: 'CORS allows "*"', cors: cors('*'), cases: [
    { site: OTHER, call: read, readable: true },
    { site: SECOND, call: read, readable: true },
    { site: SAME_HOST, call: read, readable: true },
    { site: OTHER, call: { ...read, cred: 'cookie' }, readable: true, note: 'with credentials (origin echoed, not "*")' },
  ] },
  { name: 'CORS allows only the other site', cors: () => cors(config.otherSite), cases: [
    { site: OTHER, call: read, readable: true },
    { site: SECOND, call: read, readable: false },
    { site: SAME_HOST, call: read, readable: false },
  ] },
  { name: 'CORS allows the other site and the second site', cors: () => cors(`${config.otherSite}, ${config.secondSite}`), cases: [
    { site: OTHER, call: read, readable: true },
    { site: SECOND, call: read, readable: true },
    { site: SAME_HOST, call: read, readable: false },
  ] },
  { name: 'CORS allows the other site, methods GET, POST', cors: () => cors(config.otherSite, { allow_methods: 'GET, POST, OPTIONS' }), cases: [
    { site: OTHER, call: { ...read, method: 'PUT' }, readable: false, note: 'method not allowed' },
    { site: OTHER, call: { ...read, method: 'POST' }, readable: true },
  ] },
  { name: 'CORS allows the other site, methods incl. PUT', cors: () => cors(config.otherSite, { allow_methods: 'GET, POST, PUT, OPTIONS' }), cases: [
    { site: OTHER, call: { ...read, method: 'PUT' }, readable: true },
  ] },
  { name: 'CORS allows the other site, headers Authorization, Content-Type', cors: () => cors(config.otherSite), cases: [
    { site: OTHER, call: { ...read, headers: { 'X-Custom': '1' } }, readable: false, note: 'header not allowed' },
    { site: OTHER, call: { ...read, headers: { Authorization: 'Bearer x' } }, readable: true },
  ] },
  { name: 'CORS off', cors: CORS_OFF, cases: [
    // WordPress itself echoes the origin for REST reads: the plugin's CORS settings narrow it down.
    { site: OTHER, call: read, readable: true, note: 'WordPress default' },
  ] },
];

for (const group of browserGroups) {
  test.describe(`browser reads, ${group.name}`, () => {
    test.beforeAll(() => applySettings(typeof group.cors === 'function' ? group.cors() : group.cors));

    for (const testCase of group.cases) {
      const call = testCase.call;
      const extra = [call.method !== 'GET' && call.method, call.cred === 'cookie' && 'credentials',
        call.headers && Object.keys(call.headers).join(','), testCase.note].filter(Boolean).join(', ');
      test(`${testCase.readable ? 'readable' : 'not readable'}: ${testCase.site}${extra ? ` (${extra})` : ''}`,
        async ({ callFromPage }) => {
          const result = await callFromPage({ url: callPage(SITES[testCase.site], call), route: call.route });

          expect(result.title).toMatch(testCase.readable ? /^readable HTTP/ : /^not readable/);
        });
    }
  });
}
