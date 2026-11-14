import { randomBytes } from 'node:crypto';
import { test as base, expect } from '@playwright/test';
import { config, session } from './config.mjs';
import { createUser, deleteUser, login, userExists } from './wordpress.mjs';

export { expect };

/** Every case tries to create an administrator: `blocked` = not created, `works` = created. */
export const BLOCKED = 'blocked';
export const WORKS = 'works';

export const SITES = {
  'other site': config.otherSite, // cross-site
  'same host, other port': config.otherSiteSameHost, // same-site, another origin
  'second site': config.secondSite, // cross-site, a second origin
};

/** A request (Chrome DevTools protocol) to the given REST route, not its CORS preflight. */
const isRouteCall = (request, route) =>
  request.method !== 'OPTIONS' && new URL(request.url).searchParams.get('rest_route') === route;

export const test = base.extend({
  /** A fresh username for the administrator the case tries to create. Deleted after the test. */
  marker: async ({}, use) => {
    const marker = `cross_site_${randomBytes(5).toString('hex')}`;
    await use(marker);
    deleteUser(marker);
    deleteUser(`${marker}@example.invalid`);
  },

  /**
   * A fresh subscriber, logged in through /auth: { id, login, email, password, newPassword, jwt,
   * refreshToken }. For cases that revoke, delete or change the user. Deleted after the test.
   */
  subscriber: async ({}, use) => {
    const userLogin = `cross_site_user_${randomBytes(5).toString('hex')}`;
    const password = `Pw_${randomBytes(10).toString('hex')}`;
    const id = Number(createUser(userLogin, password, 'subscriber'));
    try {
      const data = login(userLogin, password);
      await use({
        id,
        login: userLogin,
        email: `${userLogin}@example.invalid`,
        password,
        newPassword: `New_${randomBytes(10).toString('hex')}`,
        jwt: data.jwt,
        refreshToken: data.refresh_token || null,
      });
    } finally {
      deleteUser(userLogin);
    }
  },

  jwt: async ({}, use) => use(session().jwt),

  /** Asserts the outcome in the WordPress database. */
  expectOutcome: async ({}, use) => {
    await use((marker, expected) => {
      expect(userExists(marker), `administrator ${marker} must ${expected === WORKS ? '' : 'not '}be created`)
        .toBe(expected === WORKS);
    });
  },

  /**
   * Opens a page in Chrome, whose user holds the JWT cookie for WordPress (SameSite None or Lax,
   * or no cookie), and waits until the page's call to the REST route (default /wp/v2/users) finished.
   * Returns what the browser really sent, and the page's title (the result of cors.html).
   */
  callFromPage: async ({ page, context, jwt }, use, testInfo) => {
    await use(async ({ url, cookie = null, action = null, route = '/wp/v2/users', cookieJwt = jwt }) => {
      if (cookie) {
        await context.addCookies([{
          name: config.cookieName,
          value: cookieJwt,
          domain: new URL(config.target).hostname,
          path: '/',
          sameSite: cookie,
          secure: cookie === 'None',
          httpOnly: true,
        }]);
      }

      // Chrome's own network events: the headers it really sent (cookies included), also for
      // calls the page may not read (no-cors) or that end in a CORS error.
      const network = await context.newCDPSession(page);
      await network.send('Network.enable');
      const calls = new Map();
      const sentHeaders = new Map();
      const finished = new Promise((resolve) => {
        const end = (requestId, outcome) => {
          if (calls.has(requestId)) {
            resolve({ ...calls.get(requestId), outcome: calls.get(requestId).status || outcome, requestId });
          }
        };
        network.on('Network.requestWillBeSent', ({ requestId, request }) => {
          if (isRouteCall(request, route)) {
            calls.set(requestId, { method: request.method });
          }
        });
        network.on('Network.requestWillBeSentExtraInfo', ({ requestId, headers }) => {
          if (sentHeaders.has(requestId)) {
            return; // a redirect hop: keep what the first request sent
          }
          sentHeaders.set(requestId, Object.fromEntries(
            Object.entries(headers).map(([name, value]) => [name.toLowerCase(), value])));
        });
        network.on('Network.responseReceived', ({ requestId, response }) => {
          if (calls.has(requestId)) {
            // The call is done once answered: a page that does not read the body never "finishes" it.
            calls.get(requestId).status = `HTTP ${response.status}`;
            end(requestId, '');
          }
        });
        network.on('Network.loadingFinished', ({ requestId }) => end(requestId, 'finished'));
        // ERR_ABORTED: a no-cors answer hidden from the page. ERR_FAILED: refused by the browser (CORS).
        network.on('Network.loadingFailed', ({ requestId, errorText }) => end(requestId, errorText));
      });
      const timeout = new Promise((resolve) => setTimeout(() => resolve(null), 10_000));

      await page.goto(url);
      if (action) {
        await action(page);
      }
      const result = await Promise.race([finished, timeout]);
      expect(result, `the page must call ${route}`).not.toBeNull();

      await page.waitForFunction(() => document.title !== 'pending', null, { timeout: 10_000 });
      const headers = sentHeaders.get(result.requestId) || {};
      const call = {
        method: result.method,
        outcome: result.outcome,
        origin: headers.origin || null,
        referer: headers.referer || null,
        secFetchSite: headers['sec-fetch-site'] || null,
        cookieSent: (headers.cookie || '').includes(`${config.cookieName}=`),
        authorizationSent: 'authorization' in headers,
        title: await page.title(),
        // call.html (CORS mode) keeps the answer it could read: { status, body }.
        result: await page.evaluate(() => window.callResult || null),
      };
      await testInfo.attach('call', { body: JSON.stringify(call, null, 2), contentType: 'application/json' });
      return call;
    });
  },
});

/**
 * URL of other-site/call.html making one REST call. The call travels in the URL fragment, which
 * the browser never sends to a server:
 *   { route, method, data, mode: 'cors' | 'form' | 'navigate', cred: 'none' | 'header' | 'cookie',
 *     jwt, headers }
 */
export const callPage = (site, call) => {
  const url = new URL(`${site}/call.html`);
  url.searchParams.set('target', config.target);
  url.hash = new URLSearchParams({ call: JSON.stringify(call) }).toString();
  return url.toString();
};

/** URL of an other-site page: the case's username, the WordPress URL and (in the fragment) the JWT. */
export const otherSitePage = (site, page, marker, jwt) => {
  const url = new URL(`${site}/${page}`);
  url.searchParams.set('m', marker);
  url.searchParams.set('target', config.target);
  url.hash = `jwt=${jwt}`;
  return url.toString();
};
