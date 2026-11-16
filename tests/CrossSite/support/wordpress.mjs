import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
import { config } from './config.mjs';

const run = (prefix, args, options = {}) => {
  const [command, ...prefixArgs] = prefix.split(/\s+/).filter(Boolean);
  return execFileSync(command, [...prefixArgs, ...args], { encoding: 'utf8', stdio: ['pipe', 'pipe', 'pipe'], ...options });
};

export const wpCli = (...args) => run(config.wpExec, ['wp', '--allow-root', `--path=${config.wpPath}`, ...args]).trim();

/** curl from the other site's container (a real call between two hosts). */
export const otherSiteCurl = (...args) => run(config.otherSiteExec, ['curl', ...args]);

// --- settings --------------------------------------------------------------------------------

export const CORS_OFF = { enabled: 0 };

/** CORS on, allowing the given origin(s) ("*" for any, comma or newline separated). */
export const cors = (allowOrigin, overrides = {}) => ({
  enabled: 1,
  allow_origin_enabled: true,
  allow_origin: allowOrigin,
  allow_methods_enabled: true,
  allow_methods: 'GET, POST, OPTIONS',
  allow_headers_enabled: true,
  allow_headers: 'Authorization, Content-Type',
  ...overrides,
});

const merge = (base, extra) => {
  const result = { ...base };
  for (const [key, value] of Object.entries(extra)) {
    const isObject = value && typeof value === 'object' && !Array.isArray(value);
    result[key] = isObject ? merge(result[key] && typeof result[key] === 'object' ? result[key] : {}, value) : value;
  }
  return result;
};

/**
 * The fixture settings + JWT from cookie and header + "JWT on all endpoints" (API middleware),
 * with the given CORS settings, plus any other settings in `extra` (merged, nested format).
 */
export const applySettings = (corsSettings, extra = {}) => {
  const settings = [{
    general: {
      decryption_key: 'cross-site-suite-secret',
      request_jwt_cookie: 1,
      request_jwt_header: 1,
      api_middleware: { enabled: true },
      request_keys: { cookie: config.cookieName },
    },
    authorization: { enabled: '1' },
  }, extra].reduce(merge, JSON.parse(readFileSync(config.fixture, 'utf8')));
  wpCli('option', 'update', 'simple_jwt_login_settings', JSON.stringify({ ...settings, cors: corsSettings }));
};

export const getSettings = () => wpCli('option', 'get', 'simple_jwt_login_settings');
export const restoreSettings = (raw) => wpCli('option', 'update', 'simple_jwt_login_settings', raw);

// --- users -----------------------------------------------------------------------------------

export const userExists = (login) => {
  try {
    wpCli('user', 'get', login, '--field=ID');
    return true;
  } catch {
    return false;
  }
};

export const deleteUser = (login) => {
  if (userExists(login)) {
    wpCli('user', 'delete', login, '--yes');
  }
};

export const createUser = (login, password, role) =>
  wpCli('user', 'create', login, `${login}@example.invalid`, `--role=${role}`, `--user_pass=${password}`, '--porcelain');

export const createAdministrator = (login, password) => createUser(login, password, 'administrator');

/** One user meta value (e.g. first_name), read with WP-CLI. '' when not set (WP-CLI then fails). */
export const userMeta = (login, key) => {
  try {
    return wpCli('user', 'meta', 'get', login, key);
  } catch {
    return '';
  }
};

export const passwordMatches = (login, password) => {
  try {
    wpCli('user', 'check-password', login, password);
    return true;
  } catch {
    return false;
  }
};

/** Logs in through the plugin's /auth endpoint, from the other site's container: { jwt, refresh_token? }. */
export const login = (userLogin, password) => {
  const body = otherSiteCurl('-s', '-X', 'POST', `${config.targetInternal}/?rest_route=/simple-jwt-login/v1/auth`,
    '--data-urlencode', `username=${userLogin}`, '--data-urlencode', `password=${password}`);
  let data = null;
  try {
    data = JSON.parse(body)?.data;
  } catch {
    // not JSON
  }
  if (!data?.jwt) {
    throw new Error(`Could not get a JWT from ${config.targetInternal}: ${body.slice(0, 200)}`);
  }
  return data;
};

export const fetchJwt = (userLogin, password) => login(userLogin, password).jwt;

/** True when WordPress still accepts the JWT (server call, from the other site's container). */
export const jwtIsValid = (jwt) => otherSiteCurl('-s', '-o', '/dev/null', '-w', '%{http_code}',
  `${config.targetInternal}/?rest_route=/simple-jwt-login/v1/auth/validate`,
  '-H', `Authorization: Bearer ${jwt}`) === '200';

/** Raw response headers of a call from the other site's container: { name (lower case): [values] }. */
export const responseHeaders = (method, path, headers) => {
  const raw = otherSiteCurl('-s', '-o', '/dev/null', '-D', '-', '-X', method, `${config.targetInternal}${path}`,
    ...headers.flatMap((header) => ['-H', header]));
  const result = {};
  for (const line of raw.split(/\r?\n/).slice(1)) {
    const separator = line.indexOf(':');
    if (separator > 0) {
      const name = line.slice(0, separator).trim().toLowerCase();
      (result[name] ||= []).push(line.slice(separator + 1).trim());
    }
  }
  return result;
};
