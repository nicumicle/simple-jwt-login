import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const here = dirname(fileURLToPath(import.meta.url));
const env = (name, fallback) => process.env[name] || fallback;

// Defaults match docker/docker-compose.yaml. CI overrides them (see .github/workflows/cross_site_tests.yml).
export const config = {
  // Command prefix that runs WP-CLI on the WordPress site.
  wpExec: env('WP_EXEC', 'docker exec -i wordpress'),
  wpPath: env('WP_PATH', '/var/www/html'),
  // Command prefix that runs curl inside the other site's container.
  otherSiteExec: env('OTHER_SITE_EXEC', 'docker exec sjl-other-site'),
  // WordPress as the browser sees it, and as the other site's container reaches it.
  target: env('TARGET', 'http://localhost:88'),
  targetInternal: env('TARGET_INTERNAL', 'http://wordpress'),
  // The other site: a different site (cross-site), and the same host on another port (same-site).
  otherSite: env('OTHER_SITE', 'http://127.0.0.1:8090'),
  otherSiteSameHost: env('OTHER_SITE_SAME_HOST', 'http://localhost:8090'),
  // The same pages on a second port: a second, distinct origin (for "multiple allowed origins").
  secondSite: env('SECOND_SITE', 'http://127.0.0.1:8091'),
  fixture: env('FIXTURE', join(here, '../../Unit/Modules/Settings/fixtures/stored-settings.json')),
  cookieName: 'simple-jwt-login-token',
};

// The admin holding the JWT, created by global-setup.mjs.
export const session = () => ({
  jwt: process.env.SJL_CROSS_SITE_JWT,
  user: process.env.SJL_CROSS_SITE_USER,
});
