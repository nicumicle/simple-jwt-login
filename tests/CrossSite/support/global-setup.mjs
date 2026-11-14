import { randomBytes } from 'node:crypto';
import { config } from './config.mjs';
import { applySettings, CORS_OFF, createAdministrator, deleteUser, fetchJwt, getSettings, restoreSettings } from './wordpress.mjs';

const waitForOtherSite = async (site) => {
  for (let attempt = 1; attempt <= 20; attempt++) {
    try {
      if ((await fetch(`${site}/index.html`)).ok) {
        return;
      }
    } catch {
      // not up yet
    }
    await new Promise((resolve) => setTimeout(resolve, 500));
  }
  throw new Error(`The other site is not reachable at ${site} (cd docker && docker compose up -d)`);
};

/**
 * Backs up the plugin settings, creates an administrator and logs them in (JWT).
 * The returned function runs after all tests: it restores the settings and deletes the user.
 */
export default async function globalSetup() {
  await waitForOtherSite(config.otherSite);
  await waitForOtherSite(config.secondSite);
  const backup = getSettings();
  const user = `cross_site_admin_${randomBytes(4).toString('hex')}`;
  const password = `Pw_${randomBytes(12).toString('hex')}`;

  try {
    applySettings(CORS_OFF);
    createAdministrator(user, password);
    process.env.SJL_CROSS_SITE_USER = user;
    process.env.SJL_CROSS_SITE_JWT = fetchJwt(user, password);
  } catch (error) {
    deleteUser(user);
    restoreSettings(backup);
    throw error;
  }

  return () => {
    deleteUser(user);
    restoreSettings(backup);
    console.log('(plugin settings restored)');
  };
}
