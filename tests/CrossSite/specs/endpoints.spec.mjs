// The plugin's endpoints called from pages of the other site, in a real Chrome.
//
// - As a cross-origin app (CORS mode): works from an origin the CORS settings list, and the
//   browser hides the answer from any other origin.
// - As CSRF (a form or a link on the other site, the browser adding the user's JWT cookie):
//   nothing may happen. Only a JWT the page itself holds (header, URL, refresh token) works.
//
// To add a case: add a row.
//   site:   a key of SITES
//   call:   what call.html does: { route, method, data, mode: cors|form|navigate, cred: none|header|cookie }
//           Strings in `data` and `jwt` may use {jwt} {email} {login} {password} {newPassword}
//           {refreshToken} (the test's fresh subscriber) and {marker} (a fresh username).
//   cookie: the subscriber's JWT cookie in the browser: 'None', 'Lax' or null
//   effect: ({ user, marker, call, context }) => true when the call had its effect
//   expect: WORKS (effect happened) or BLOCKED (it did not)
//   status: optional, the HTTP status Chrome received (also when the page may not read it)
import { config } from '../support/config.mjs';
import { BLOCKED, callPage, expect, SITES, test, WORKS } from '../support/fixtures.mjs';
import { applySettings, cors, jwtIsValid, passwordMatches, userExists } from '../support/wordpress.mjs';

const OTHER = 'other site';
const SECOND = 'second site';
const SAME_HOST = 'same host, other port';
const NAMESPACE = '/simple-jwt-login/v1';

const endpointSettings = {
  login: { enabled: true },
  register: { enabled: true, new_user_profile: 'subscriber' },
  delete_user: { enabled: true },
  reset_password: { enabled: true, return_jwt: true },
  authorization: {
    enabled: '1',
    validate_token_enabled: '1',
    revoke_token_enabled: '1',
    refresh_token_enabled: '1',
    refresh_token_key: 'cross-site-refresh-secret',
  },
};

// --- effects ---------------------------------------------------------------------------------
const answered = (status) => ({ call }) => call.result?.status === status;
const answeredWith = (key) => ({ call }) => call.result?.status === 200 && JSON.parse(call.result.body)?.data?.[key];
const userRegistered = ({ marker }) => userExists(`${marker}@example.invalid`);
const jwtRevoked = ({ user }) => !jwtIsValid(user.jwt);
const userDeleted = ({ user }) => !userExists(user.login);
const passwordChanged = ({ user }) => passwordMatches(user.login, user.newPassword);
const loggedIn = async ({ context }) => (await context.cookies(config.target))
  .some((cookie) => cookie.name.startsWith('wordpress_logged_in_'));
const callSucceeded = ({ call }) => /^HTTP 2\d\d$/.test(call.outcome);
/** WordPress answered as the subscriber (core /wp/v2/users/me). */
const actsAsUser = ({ call, user }) => call.result?.status === 200 && JSON.parse(call.result.body)?.id === user.id;

const auth = { route: `${NAMESPACE}/auth`, method: 'POST', data: { username: '{login}', password: '{password}' } };
const refresh = { route: `${NAMESPACE}/auth/refresh`, method: 'POST', data: { refresh_token: '{refreshToken}' } };
const register = { route: `${NAMESPACE}/users`, method: 'POST', data: { email: '{marker}@example.invalid', password: 'Xk9qz_{marker}' } };
const validate = { route: `${NAMESPACE}/auth/validate`, method: 'GET' };
const revoke = { route: `${NAMESPACE}/auth/revoke`, method: 'POST' };
const deleteUser = { route: `${NAMESPACE}/users`, method: 'DELETE' };
const resetPassword = { route: `${NAMESPACE}/user/reset_password`, method: 'PUT', data: { email: '{email}', new_password: '{newPassword}' } };
const autologin = { route: `${NAMESPACE}/autologin`, method: 'GET' };
const usersMe = { route: '/wp/v2/users/me', method: 'GET' };

const cases = [
  // Log in with a password (no JWT involved).
  { name: 'auth: app on the listed origin gets a JWT', site: OTHER, call: { ...auth, mode: 'cors' }, effect: answeredWith('jwt'), expect: WORKS },
  { name: 'auth: answer hidden from an origin not listed', site: SECOND, call: { ...auth, mode: 'cors' }, effect: answeredWith('jwt'), expect: BLOCKED },
  // Refresh with the opaque refresh token (not sent by the browser on its own).
  { name: 'refresh: app on the listed origin gets a new JWT', site: OTHER, call: { ...refresh, mode: 'cors' }, effect: answeredWith('jwt'), expect: WORKS },
  { name: 'refresh: answer hidden from an origin not listed', site: SECOND, call: { ...refresh, mode: 'cors' }, effect: answeredWith('jwt'), expect: BLOCKED },
  // Register (no JWT involved).
  { name: 'register: app on the listed origin', site: OTHER, call: { ...register, mode: 'cors' }, effect: userRegistered, expect: WORKS },
  { name: 'register: origin not listed (preflight refused, never sent)', site: SECOND, call: { ...register, mode: 'cors' }, effect: userRegistered, expect: BLOCKED },
  // Validate the JWT.
  { name: 'validate: app on the listed origin, Authorization header', site: OTHER, call: { ...validate, mode: 'cors', cred: 'header' }, effect: answered(200), expect: WORKS },
  { name: 'validate: app on the listed origin, JWT cookie', site: OTHER, cookie: 'None', call: { ...validate, mode: 'cors', cred: 'cookie' }, effect: answered(200), expect: WORKS },
  { name: 'validate: origin not listed, Authorization header', site: SECOND, call: { ...validate, mode: 'cors', cred: 'header' }, effect: answered(200), expect: BLOCKED },
  { name: 'validate: link on another site, JWT cookie (CSRF)', site: SECOND, cookie: 'Lax', call: { ...validate, mode: 'navigate' }, effect: callSucceeded, expect: BLOCKED },
  // Revoke the JWT.
  { name: 'revoke: app on the listed origin, Authorization header', site: OTHER, call: { ...revoke, mode: 'cors', cred: 'header', data: { JWT: '{jwt}' } }, effect: jwtRevoked, expect: WORKS },
  { name: 'revoke: form on another site, JWT cookie (CSRF)', site: SECOND, cookie: 'None', call: { ...revoke, mode: 'form' }, effect: jwtRevoked, expect: BLOCKED },
  { name: 'revoke: form on the same host, other port, JWT cookie (CSRF)', site: SAME_HOST, cookie: 'Lax', call: { ...revoke, mode: 'form' }, effect: jwtRevoked, expect: BLOCKED },
  // Delete the user.
  { name: 'delete user: app on the listed origin, Authorization header', site: OTHER, call: { ...deleteUser, mode: 'cors', cred: 'header' }, effect: userDeleted, expect: WORKS },
  { name: 'delete user: app on the listed origin, JWT cookie', site: OTHER, cookie: 'None', call: { ...deleteUser, mode: 'cors', cred: 'cookie' }, effect: userDeleted, expect: WORKS },
  { name: 'delete user: origin not listed, Authorization header', site: SECOND, call: { ...deleteUser, mode: 'cors', cred: 'header' }, effect: userDeleted, expect: BLOCKED },
  { name: 'delete user: form on another site, JWT cookie (CSRF)', site: SECOND, cookie: 'None', call: { ...deleteUser, mode: 'form' }, effect: userDeleted, expect: BLOCKED },
  { name: 'delete user: link on another site, JWT cookie (CSRF)', site: SECOND, cookie: 'Lax', call: { ...deleteUser, mode: 'navigate' }, effect: userDeleted, expect: BLOCKED },
  // Change the password with the JWT.
  { name: 'reset password: app on the listed origin, Authorization header', site: OTHER, call: { ...resetPassword, mode: 'cors', cred: 'header' }, effect: passwordChanged, expect: WORKS },
  { name: 'reset password: form on another site, JWT cookie (CSRF)', site: SECOND, cookie: 'None', call: { ...resetPassword, mode: 'form' }, effect: passwordChanged, expect: BLOCKED },
  // Autologin: a link holding the JWT (e.g. from an email) works from anywhere, the cookie never.
  { name: 'autologin: link with the JWT in the URL, from another site', site: SECOND, call: { ...autologin, mode: 'navigate', data: { JWT: '{jwt}' } }, effect: loggedIn, expect: WORKS },
  { name: 'autologin: link on another site, JWT cookie (CSRF)', site: SECOND, cookie: 'Lax', call: { ...autologin, mode: 'navigate' }, effect: loggedIn, expect: BLOCKED },
  { name: 'autologin: link on the same host, other port, JWT cookie (CSRF)', site: SAME_HOST, cookie: 'Lax', call: { ...autologin, mode: 'navigate' }, effect: loggedIn, expect: BLOCKED },
  // WordPress core endpoints, through "JWT on all endpoints" (API middleware).
  { name: 'WP /users/me: app on the listed origin, Authorization header', site: OTHER, call: { ...usersMe, mode: 'cors', cred: 'header' }, effect: actsAsUser, expect: WORKS, status: 200 },
  { name: 'WP /users/me: app on the listed origin, JWT cookie', site: OTHER, cookie: 'None', call: { ...usersMe, mode: 'cors', cred: 'cookie' }, effect: actsAsUser, expect: WORKS, status: 200 },
  { name: 'WP /users/me: app on the listed origin, no JWT', site: OTHER, call: { ...usersMe, mode: 'cors' }, effect: actsAsUser, expect: BLOCKED, status: 401 },
  { name: 'WP /users/me: app on the listed origin, invalid JWT', site: OTHER, call: { ...usersMe, mode: 'cors', cred: 'header', jwt: 'not.a.jwt' }, effect: actsAsUser, expect: BLOCKED, status: 400 }, // the plugin's own JWT error
  { name: 'WP /users/me: origin not listed, Authorization header', site: SECOND, call: { ...usersMe, mode: 'cors', cred: 'header' }, effect: actsAsUser, expect: BLOCKED },
  { name: 'WP /users/me: link on another site, JWT cookie (CSRF)', site: SECOND, cookie: 'Lax', call: { ...usersMe, mode: 'navigate' }, effect: callSucceeded, expect: BLOCKED, status: 401 },
  { name: 'WP /users/me: link with the JWT in the URL, from another site', site: SECOND, call: { ...usersMe, mode: 'navigate', data: { JWT: '{jwt}' } }, effect: callSucceeded, expect: WORKS, status: 200 },
];

const fill = (value, values) => (typeof value === 'string'
  ? value.replace(/\{(\w+)\}/g, (match, key) => (key in values ? values[key] : match))
  : value);

test.describe('plugin endpoints from the other site (CORS allows the other site only)', () => {
  test.beforeAll(() => applySettings(cors(config.otherSite, { allow_methods: 'GET, POST, PUT, DELETE, OPTIONS' }), endpointSettings));

  for (const testCase of cases) {
    test(`${testCase.expect}: ${testCase.name}`, async ({ subscriber, marker, context, callFromPage }) => {
      const values = { ...subscriber, marker };
      const data = Object.fromEntries(Object.entries(testCase.call.data || {}).map(([key, value]) => [key, fill(value, values)]));
      const call = await callFromPage({
        url: callPage(SITES[testCase.site], { ...testCase.call, data, jwt: fill(testCase.call.jwt || '{jwt}', values) }),
        route: testCase.call.route,
        cookie: testCase.cookie || null,
        cookieJwt: subscriber.jwt,
      });

      if (testCase.status !== undefined) {
        expect(call.outcome, 'HTTP status received by Chrome').toBe(`HTTP ${testCase.status}`);
      }
      const happened = Boolean(await testCase.effect({ user: subscriber, marker, call, context }));
      expect(happened, `${testCase.expect === WORKS ? 'must' : 'must not'} have its effect (call: ${call.outcome}, ${call.title})`)
        .toBe(testCase.expect === WORKS);
    });
  }
});
