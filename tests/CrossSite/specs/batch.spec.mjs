// /batch/v1 from the other site, in a real Chrome. WordPress runs every sub-request of a batch
// through the same checks as a direct call, so Protect Endpoints must check each sub-request:
// wrapping a protected call in a batch must not skip the rule.
//
// Each case asks a batch to change the subscriber's own first name (POST /wp/v2/users/<id>),
// with /wp/v2/users protected by Protect Endpoints.
//   effect: the first name changed
//   guard:  optional, the sub-request was refused by Protect Endpoints itself (error_code 75),
//           not by a WordPress capability check further down
import { config } from '../support/config.mjs';
import { BLOCKED, callPage, expect, SITES, test, WORKS } from '../support/fixtures.mjs';
import { applySettings, cors, userMeta } from '../support/wordpress.mjs';

const OTHER = 'other site';
const SECOND = 'second site';
const ERR_PROTECT_ENDPOINTS_MISSING_JWT = 75;

const protectUsers = {
  protect_endpoint: {
    enabled: 1,
    default_action: 'allow_all',
    rules_url: ['/wp/v2/users'],
    rules_method: ['ALL'],
    rules_match: ['STARTS_WITH'],
    rules_type: ['protected'],
    rules_roles: [''],
  },
};

const batch = {
  route: '/batch/v1',
  method: 'POST',
  data: { requests: [{ method: 'POST', path: '/wp/v2/users/{id}', body: { first_name: '{marker}' } }] },
};
// A form can only send flat fields: PHP turns these names back into the same nested list.
const batchForm = {
  ...batch,
  data: {
    'requests[0][method]': 'POST',
    'requests[0][path]': '/wp/v2/users/{id}',
    'requests[0][body][first_name]': '{marker}',
  },
};

const cases = [
  { name: 'app on the listed origin, Authorization header', site: OTHER, call: { ...batch, mode: 'cors', cred: 'header' }, expect: WORKS, status: 207 },
  { name: 'app on the listed origin, no JWT', site: OTHER, call: { ...batch, mode: 'cors' }, expect: BLOCKED, status: 207, guard: true },
  { name: 'origin not listed, Authorization header (preflight refused)', site: SECOND, call: { ...batch, mode: 'cors', cred: 'header' }, expect: BLOCKED },
  { name: 'form on another site, JWT cookie (CSRF)', site: SECOND, cookie: 'None', call: { ...batchForm, mode: 'form' }, expect: BLOCKED },
];

/** Replaces {placeholders} in every string of a (nested) value. */
const fill = (value, values) => {
  if (typeof value === 'string') {
    return value.replace(/\{(\w+)\}/g, (match, key) => (key in values ? String(values[key]) : match));
  }
  if (Array.isArray(value)) {
    return value.map((item) => fill(item, values));
  }
  if (value && typeof value === 'object') {
    return Object.fromEntries(Object.entries(value).map(([key, item]) => [key, fill(item, values)]));
  }
  return value;
};

test.describe('/batch/v1 from the other site (CORS allows the other site, /wp/v2/users protected)', () => {
  test.beforeAll(() => applySettings(cors(config.otherSite), protectUsers));

  for (const testCase of cases) {
    test(`${testCase.expect}: batch: ${testCase.name}`, async ({ subscriber, marker, callFromPage }) => {
      const values = { ...subscriber, marker };
      const call = await callFromPage({
        url: callPage(SITES[testCase.site], fill({ ...testCase.call, jwt: '{jwt}' }, values)),
        route: '/batch/v1',
        cookie: testCase.cookie || null,
        cookieJwt: subscriber.jwt,
      });

      if (testCase.status !== undefined) {
        expect(call.outcome, 'HTTP status received by Chrome').toBe(`HTTP ${testCase.status}`);
      }
      if (testCase.guard) {
        const subResponse = JSON.parse(call.result.body).responses[0];
        expect(subResponse.body?.data?.error_code, `refused by Protect Endpoints (${JSON.stringify(subResponse)})`)
          .toBe(ERR_PROTECT_ENDPOINTS_MISSING_JWT);
      }
      const changed = userMeta(subscriber.login, 'first_name') === marker;
      expect(changed, `the first name ${testCase.expect === WORKS ? 'must' : 'must not'} change (call: ${call.outcome}, ${call.title})`)
        .toBe(testCase.expect === WORKS);
    });
  }
});
