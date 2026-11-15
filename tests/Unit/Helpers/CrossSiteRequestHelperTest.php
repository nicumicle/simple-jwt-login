<?php

namespace SimpleJwtLoginTests\Unit\Helpers;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Helpers\CrossSiteRequestHelper;
use SimpleJWTLogin\Helpers\ServerHelper;

class CrossSiteRequestHelperTest extends TestCase
{
    /**
     * @param array<string,string> $server
     * @param string[] $trustedUrls
     */
    #[DataProvider('requestProvider')]
    public function testIsCrossSite(array $server, array $trustedUrls, bool $expected): void
    {
        $server = array_merge(['HTTP_HOST' => 'site.test'], $server);

        $this->assertSame(
            $expected,
            CrossSiteRequestHelper::isCrossSite(new ServerHelper($server), $trustedUrls)
        );
    }

    /**
     * X-Forwarded-Proto is only read behind a trusted proxy.
     *
     * @param array<string,string> $server
     */
    #[DataProvider('trustedProxyProvider')]
    public function testIsCrossSiteBehindTrustedProxy(array $server, bool $expected): void
    {
        $server = array_merge(['HTTP_HOST' => 'site.test'], $server);

        $this->assertSame(
            $expected,
            CrossSiteRequestHelper::isCrossSite(ServerHelper::withTrustedProxyHeaders($server), [])
        );
    }

    /**
     * @return array<string,array{array<string,string>, bool}>
     */
    public static function trustedProxyProvider(): array
    {
        return [
            'Origin https behind a TLS proxy' => [
                ['HTTP_X_FORWARDED_PROTO' => 'https, http', 'HTTP_ORIGIN' => 'https://site.test'],
                false,
            ],
            'Origin http when the proxy forwards https' => [
                ['HTTP_X_FORWARDED_PROTO' => 'https', 'HTTP_ORIGIN' => 'http://site.test'],
                true,
            ],
            'unknown forwarded scheme falls back to the connection' => [
                ['HTTPS' => 'on', 'HTTP_X_FORWARDED_PROTO' => 'ftp', 'HTTP_ORIGIN' => 'https://site.test'],
                false,
            ],
        ];
    }

    /**
     * @return array<string,array{array<string,string>, string[], bool}>
     */
    public static function requestProvider(): array
    {
        return [
            'no provenance headers (non-browser client)' => [[], [], false],
            'fetch metadata cross-site' => [['HTTP_SEC_FETCH_SITE' => 'cross-site'], [], true],
            'fetch metadata cross-site wins over same Origin' => [
                ['HTTP_SEC_FETCH_SITE' => 'cross-site', 'HTTP_ORIGIN' => 'http://site.test'],
                [],
                true,
            ],
            'fetch metadata same-origin' => [['HTTP_SEC_FETCH_SITE' => 'same-origin'], [], false],
            'fetch metadata none (typed URL)' => [['HTTP_SEC_FETCH_SITE' => 'NONE'], [], false],
            'fetch metadata same-site falls back to Origin' => [
                ['HTTP_SEC_FETCH_SITE' => 'same-site', 'HTTP_ORIGIN' => 'http://evil.site.test'],
                [],
                true,
            ],
            'fetch metadata same-site without Origin or Referer' => [['HTTP_SEC_FETCH_SITE' => 'same-site'], [], true],
            'fetch metadata same-site with this site as Referer' => [
                ['HTTP_SEC_FETCH_SITE' => 'same-site', 'HTTP_REFERER' => 'http://site.test/page'],
                [],
                false,
            ],
            'Origin of another site' => [['HTTP_ORIGIN' => 'http://attacker.example'], [], true],
            'Origin null' => [['HTTP_ORIGIN' => 'null'], [], true],
            'Origin equal to Host' => [['HTTP_ORIGIN' => 'HTTP://SITE.test'], [], false],
            'Origin with default port equal to Host' => [
                ['HTTPS' => 'on', 'HTTP_ORIGIN' => 'https://site.test:443'],
                [],
                false,
            ],
            'Origin with another scheme (https page, http site)' => [['HTTP_ORIGIN' => 'https://site.test'], [], true],
            'Origin with another scheme (http page, https site)' => [
                ['HTTPS' => 'on', 'HTTP_ORIGIN' => 'http://site.test'],
                [],
                true,
            ],
            'Origin https on port 443' => [['SERVER_PORT' => '443', 'HTTP_ORIGIN' => 'https://site.test'], [], false],
            'X-Forwarded-Proto ignored without a trusted proxy' => [
                ['HTTP_X_FORWARDED_PROTO' => 'https', 'HTTP_ORIGIN' => 'https://site.test'],
                [],
                true,
            ],
            'X-Forwarded-Proto cannot downgrade an https site without a trusted proxy' => [
                ['HTTPS' => 'on', 'HTTP_X_FORWARDED_PROTO' => 'http', 'HTTP_ORIGIN' => 'http://site.test'],
                [],
                true,
            ],
            'Origin https behind a TLS proxy (site URL)' => [
                ['HTTP_ORIGIN' => 'https://site.test'],
                ['https://site.test'],
                false,
            ],
            'Referer with another scheme' => [['HTTP_REFERER' => 'https://site.test/page'], [], true],
            'Origin with another port' => [['HTTP_ORIGIN' => 'http://site.test:8080'], [], true],
            'Origin equal to Host with port' => [
                ['HTTP_HOST' => 'site.test:8080', 'HTTP_ORIGIN' => 'http://site.test:8080'],
                [],
                false,
            ],
            'Host with default port' => [
                ['HTTP_HOST' => 'site.test:80', 'HTTP_ORIGIN' => 'http://site.test'],
                [],
                false,
            ],
            'Origin equal to the site URL' => [
                ['HTTP_HOST' => 'internal:8000', 'HTTP_ORIGIN' => 'https://public.test'],
                ['https://public.test/wp'],
                false,
            ],
            'Origin wins over a same-site Referer' => [
                ['HTTP_ORIGIN' => 'http://attacker.example', 'HTTP_REFERER' => 'http://site.test/'],
                [],
                true,
            ],
            'Referer of another site' => [['HTTP_REFERER' => 'http://attacker.example/csrf.html'], [], true],
            'Referer of this site' => [['HTTP_REFERER' => 'http://site.test/wp-admin/'], [], false],
            'Referer with a look-alike host' => [['HTTP_REFERER' => 'http://site.test.attacker.example/'], [], true],
            'malformed Origin' => [['HTTP_ORIGIN' => 'http:///'], [], true],
            'empty site URL is ignored' => [['HTTP_HOST' => '', 'HTTP_ORIGIN' => 'http://site.test'], [''], true],
        ];
    }

    /**
     * @param array<string,string> $server
     * @param string[] $trustedOrigins
     */
    #[DataProvider('trustedOriginProvider')]
    public function testTrustedOrigins(array $server, array $trustedOrigins, bool $expected): void
    {
        $server = array_merge(['HTTP_HOST' => 'site.test', 'HTTP_SEC_FETCH_SITE' => 'cross-site'], $server);

        $this->assertSame(
            $expected,
            CrossSiteRequestHelper::isCrossSite(new ServerHelper($server), [], $trustedOrigins)
        );
    }

    /**
     * @return array<string,array{array<string,string>, string[], bool}>
     */
    public static function trustedOriginProvider(): array
    {
        $app = ['HTTP_ORIGIN' => 'https://app.example'];

        return [
            'listed origin' => [$app, ['https://app.example'], false],
            'listed origin, case and trailing slash' => [$app, ['HTTPS://APP.example/'], false],
            'other scheme' => [$app, ['http://app.example'], true],
            'other port' => [$app, ['https://app.example:8443'], true],
            'look-alike host' => [['HTTP_ORIGIN' => 'https://app.example.attacker.test'], ['https://app.example'], true],
            'wildcard' => [$app, ['*'], false],
            'wildcard needs an Origin' => [[], ['*'], true],
            'Referer is not an origin claim' => [['HTTP_REFERER' => 'https://app.example/page'], ['https://app.example'], true],
            'empty list' => [$app, [], true],
        ];
    }
}
