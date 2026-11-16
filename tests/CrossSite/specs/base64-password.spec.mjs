// /auth with "password is base64" on, called by an app on the other site, in a real Chrome.
// The app sends base64(password): every password a user can set must log in, including quotes
// and backslashes, which WordPress slashes before hashing.
//
//   password: the user's real password (set with WP-CLI)
//   sent:     what the app sends as `password` ({base64} = base64 of the real password)
import { randomBytes } from 'node:crypto';
import { config } from '../support/config.mjs';
import { BLOCKED, callPage, expect, SITES, test, WORKS } from '../support/fixtures.mjs';
import { applySettings, cors, createUser, deleteUser } from '../support/wordpress.mjs';

const OTHER = 'other site';
const SECOND = 'second site';
const ERR_WRONG_CREDENTIALS = 48;

const base64Login = {
  login: { enabled: true },
  authorization: { enabled: '1', password_base64: true },
};

const cases = [
  { name: 'plain password', site: OTHER, password: 'Plain_{random}', sent: '{base64}', expect: WORKS },
  { name: 'password with quotes', site: OTHER, password: 'It\'s_"{random}"', sent: '{base64}', expect: WORKS },
  { name: 'password with a backslash', site: OTHER, password: 'Back\\slash_{random}', sent: '{base64}', expect: WORKS },
  { name: 'raw password instead of base64', site: OTHER, password: 'Raw!pw_{random}', sent: '{password}', expect: BLOCKED, errorCode: ERR_WRONG_CREDENTIALS },
  { name: 'answer hidden from an origin not listed', site: SECOND, password: 'It\'s_"{random}"', sent: '{base64}', expect: BLOCKED },
];

test.describe('/auth with base64 passwords, from the other site (CORS allows the other site)', () => {
  test.beforeAll(() => applySettings(cors(config.otherSite), base64Login));

  for (const testCase of cases) {
    test(`${testCase.expect}: base64 auth: ${testCase.name}`, async ({ callFromPage }) => {
      const userLogin = `cross_site_b64_${randomBytes(5).toString('hex')}`;
      const password = testCase.password.replace('{random}', randomBytes(6).toString('hex'));
      const sent = testCase.sent
        .replace('{base64}', Buffer.from(password, 'utf8').toString('base64'))
        .replace('{password}', password);
      createUser(userLogin, password, 'subscriber');
      try {
        const call = await callFromPage({
          url: callPage(SITES[testCase.site], {
            route: '/simple-jwt-login/v1/auth',
            method: 'POST',
            mode: 'cors',
            data: { username: userLogin, password: sent },
          }),
          route: '/simple-jwt-login/v1/auth',
        });

        const body = call.result ? JSON.parse(call.result.body) : null;
        const gotJwt = call.result?.status === 200 && Boolean(body?.data?.jwt);
        expect(gotJwt, `${testCase.expect === WORKS ? 'must' : 'must not'} get a JWT (call: ${call.outcome}, ${call.title}, ${call.result?.body})`)
          .toBe(testCase.expect === WORKS);
        if (testCase.errorCode !== undefined) {
          expect(body?.data?.error_code, 'error code').toBe(testCase.errorCode);
        }
      } finally {
        deleteUser(userLogin);
      }
    });
  }
});
