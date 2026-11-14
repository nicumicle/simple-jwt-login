<?php

namespace SimpleJWTLogin\Helpers;

/**
 * Detects requests started by another site (CSRF).
 *
 * A JWT read from a cookie or from the PHP session is an ambient credential:
 * the browser sends it with every request, including requests started by an
 * attacker's page. A JWT (cookie, session or Authorization header) must only
 * authenticate requests that come from this site or from a trusted origin.
 *
 * The check runs for every HTTP method on purpose: WordPress turns a GET into a
 * POST through `_method` / `X-HTTP-Method-Override`, so a check limited to
 * unsafe methods would let a cross-site GET navigation through.
 */
class CrossSiteRequestHelper
{
    /**
     * @var array<string,int>
     */
    private static $defaultPorts = ['http' => 80, 'https' => 443];

    /**
     * Decision order:
     *  1. `Sec-Fetch-Site` (set by the browser, cannot be forged by a page):
     *     `cross-site` is rejected, `same-origin` / `none` are accepted.
     *  2. `Origin`, then `Referer`: accepted only when its origin (scheme, host
     *     and port) is this site's.
     *  3. `same-site` with neither header (sibling subdomain hiding them): rejected.
     *  4. No provenance header at all: a non-browser client (server-to-server,
     *     mobile app, CLI). A browser always sends `Sec-Fetch-Site` or `Origin`
     *     on a cross-site request that can change state.
     *
     * An `Origin` listed in $trustedOrigins (e.g. the CORS allowlist) is accepted
     * before all of the above: a cross-origin app sends `Sec-Fetch-Site: cross-site`
     * too. Only `Origin` can match it, `Referer` is not an origin claim. A `*` entry
     * accepts every origin that sends an `Origin` header.
     *
     * @param ServerHelper $serverHelper
     * @param string[] $trustedUrls Site URLs that count as "this site", besides the request Host.
     * @param string[] $trustedOrigins Full origins (scheme://host[:port]) allowed to call cross-site.
     * @return bool
     */
    public static function isCrossSite($serverHelper, $trustedUrls, $trustedOrigins = [])
    {
        if (self::isTrustedOrigin($serverHelper, $trustedOrigins)) {
            return false;
        }

        $fetchSite = strtolower(trim((string) $serverHelper->getHeader('Sec-Fetch-Site')));
        if ($fetchSite === 'cross-site') {
            return true;
        }
        if ($fetchSite === 'same-origin' || $fetchSite === 'none') {
            return false;
        }

        $siteOrigins = self::siteOrigins($serverHelper, $trustedUrls);
        foreach (['Origin', 'Referer'] as $headerName) {
            $headerValue = trim((string) $serverHelper->getHeader($headerName));
            if ($headerValue !== '') {
                return !in_array(self::originOf($headerValue), $siteOrigins, true);
            }
        }

        // `same-site` is another origin of the same site (a sibling subdomain or
        // port), which can hide Origin and Referer: never treat it as this site.
        return $fetchSite === 'same-site';
    }

    /**
     * @param ServerHelper $serverHelper
     * @param string[] $trustedOrigins
     * @return bool
     */
    protected static function isTrustedOrigin($serverHelper, $trustedOrigins)
    {
        $origin = rtrim(trim((string) $serverHelper->getHeader('Origin')), '/');
        if ($origin === '') {
            return false;
        }

        foreach ($trustedOrigins as $trustedOrigin) {
            if ($trustedOrigin === '*' || strcasecmp(rtrim((string) $trustedOrigin, '/'), $origin) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Origins of this site: the request's own (scheme + Host) and the site URLs.
     *
     * @param ServerHelper $serverHelper
     * @param string[] $trustedUrls
     * @return string[]
     */
    protected static function siteOrigins($serverHelper, $trustedUrls)
    {
        $origins = [];
        $requestHost = trim((string) $serverHelper->getHeader('Host'));
        if ($requestHost !== '') {
            $origins[] = self::originOf(self::requestScheme($serverHelper) . '://' . $requestHost);
        }
        foreach ($trustedUrls as $trustedUrl) {
            $origins[] = self::originOf((string) $trustedUrl);
        }

        return array_values(array_filter($origins, function ($origin) {
            return $origin !== null;
        }));
    }

    /**
     * Scheme the client used. `X-Forwarded-Proto` (TLS-terminating proxy) cannot
     * be set by a cross-site form or navigation, so it is safe for this check.
     *
     * @param ServerHelper $serverHelper
     * @return string
     */
    protected static function requestScheme($serverHelper)
    {
        $forwarded = explode(',', (string) $serverHelper->getHeader('X-Forwarded-Proto'));
        $forwardedScheme = strtolower(trim($forwarded[0]));
        if ($forwardedScheme === 'https' || $forwardedScheme === 'http') {
            return $forwardedScheme;
        }

        return $serverHelper->isHttps() ? 'https' : 'http';
    }

    /**
     * Lower-cased `scheme://host[:port]` of a URL, default ports removed.
     * Null when the URL has no scheme or host (e.g. the `null` Origin).
     *
     * @param string $url
     * @return string|null
     */
    protected static function originOf($url)
    {
        //phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url
        $parts = parse_url($url);
        if (!is_array($parts) || empty($parts['host']) || empty($parts['scheme'])) {
            return null;
        }

        $scheme = strtolower($parts['scheme']);
        $origin = $scheme . '://' . strtolower($parts['host']);
        if (!isset($parts['port'])) {
            return $origin;
        }

        $port = (int) $parts['port'];
        if (isset(self::$defaultPorts[$scheme]) && self::$defaultPorts[$scheme] === $port) {
            return $origin;
        }

        return $origin . ':' . $port;
    }
}
