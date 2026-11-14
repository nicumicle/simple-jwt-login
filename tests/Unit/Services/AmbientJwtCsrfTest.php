<?php

namespace SimpleJwtLoginTests\Unit\Services;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Helpers\ServerHelper;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;
use SimpleJWTLogin\Services\RouteService;

/**
 * A header, cookie or session JWT must be ignored on cross-site requests (CSRF),
 * unless the origin is listed in the CORS settings ("*" trusts the header only).
 * URL JWTs are not affected.
 */
class AmbientJwtCsrfTest extends TestCase
{
    private const COOKIE_KEY = 'simple-jwt-login-token';

    /**
     * @param array<string,string> $server
     * @param array<string,mixed>  $request
     * @param array<string,mixed>  $settings
     */
    private function routeService(array $server, array $request = [], array $settings = []): RouteService
    {
        $wpData = $this->createStub(WordPressDataInterface::class);
        $wpData->method('getOptionFromDatabase')->willReturn(json_encode(array_merge([
            'request_jwt_cookie'  => 1,
            'request_jwt_session' => 1,
            'request_jwt_url'     => 1,
            'request_jwt_header'  => 1,
        ], $settings)));
        $wpData->method('getSiteUrl')->willReturn('https://public.test');

        return (new RouteService())
            ->withSettings(new SimpleJWTLoginSettings($wpData))
            ->withRequest($request)
            ->withCookies([self::COOKIE_KEY => 'cookie.jwt.value'])
            ->withSession([self::COOKIE_KEY => 'session.jwt.value'])
            ->withServerHelper(new ServerHelper(array_merge(['HTTP_HOST' => 'site.test'], $server)));
    }

    /**
     * @param array<string,string> $server
     */
    #[DataProvider('provenanceProvider')]
    public function testCookieJwtDependsOnRequestProvenance(array $server, ?string $expected): void
    {
        $this->assertSame($expected, $this->routeService($server)->getJwtFromRequestHeaderOrCookie());
    }

    /**
     * @return array<string,array{array<string,string>, string|null}>
     */
    public static function provenanceProvider(): array
    {
        return [
            'same origin' => [['HTTP_ORIGIN' => 'http://site.test'], 'cookie.jwt.value'],
            'site URL origin' => [['HTTP_ORIGIN' => 'https://public.test'], 'cookie.jwt.value'],
            'non-browser client' => [[], 'cookie.jwt.value'],
            'attacker origin' => [['HTTP_ORIGIN' => 'http://attacker.example'], null],
            'cross-site navigation' => [['HTTP_SEC_FETCH_SITE' => 'cross-site'], null],
        ];
    }

    public function testSessionJwtIsIgnoredOnCrossSiteRequest(): void
    {
        $routeService = $this->routeService(['HTTP_SEC_FETCH_SITE' => 'cross-site'])
            ->withCookies([]);

        $this->assertNull($routeService->getJwtFromRequestHeaderOrCookie());
    }

    public function testSessionJwtIsUsedOnSameOriginRequest(): void
    {
        $routeService = $this->routeService(['HTTP_SEC_FETCH_SITE' => 'same-origin'])
            ->withCookies([]);

        $this->assertSame('session.jwt.value', $routeService->getJwtFromRequestHeaderOrCookie());
    }

    public function testUrlJwtIsStillReadOnCrossSiteRequest(): void
    {
        $routeService = $this->routeService(['HTTP_SEC_FETCH_SITE' => 'cross-site'], ['JWT' => 'url.jwt.value']);

        $this->assertSame('url.jwt.value', $routeService->getJwtFromRequestHeaderOrCookie());
    }

    /**
     * @param array<string,string> $server
     */
    #[DataProvider('cookieInRequestProvider')]
    public function testCookieMergedIntoRequestIsStillAmbient(array $server, ?string $expected): void
    {
        // $_REQUEST with variables_order=EGPCS: the cookie "jwt" shows up as a request value.
        $routeService = $this->routeService($server, ['JWT' => 'cookie.jwt.value'])
            ->withCookies(['jwt' => 'cookie.jwt.value'])
            ->withSession([]);

        $this->assertSame($expected, $routeService->getJwtFromRequestHeaderOrCookie());
    }

    /**
     * @return array<string,array{array<string,string>, string|null}>
     */
    public static function cookieInRequestProvider(): array
    {
        return [
            'cross-site request' => [['HTTP_SEC_FETCH_SITE' => 'cross-site'], null],
            'same-origin request' => [['HTTP_SEC_FETCH_SITE' => 'same-origin'], 'cookie.jwt.value'],
        ];
    }

    /**
     * @param array<string,string> $server
     * @param array<string,mixed>  $settings
     */
    #[DataProvider('headerProvider')]
    public function testHeaderJwtDependsOnRequestProvenance(array $server, array $settings, ?string $expected): void
    {
        $routeService = $this->routeService(
            array_merge(['HTTP_AUTHORIZATION' => 'Bearer header.jwt.value'], $server),
            [],
            $settings
        )->withCookies([])->withSession([]);

        $this->assertSame($expected, $routeService->getJwtFromRequestHeaderOrCookie());
    }

    /**
     * @return array<string,array{array<string,string>, array<string,mixed>, string|null}>
     */
    public static function headerProvider(): array
    {
        $cors = function (string $origins): array {
            return ['cors' => ['enabled' => 1, 'allow_origin_enabled' => 1, 'allow_origin' => $origins]];
        };
        $spa = ['HTTP_SEC_FETCH_SITE' => 'cross-site', 'HTTP_ORIGIN' => 'https://app.example'];

        return [
            'non-browser client' => [[], [], 'header.jwt.value'],
            'same origin' => [['HTTP_SEC_FETCH_SITE' => 'same-origin'], [], 'header.jwt.value'],
            'attacker origin' => [['HTTP_ORIGIN' => 'http://attacker.example'], [], null],
            'cross-site fetch' => [$spa, [], null],
            'origin in CORS list' => [$spa, $cors('https://other.example, https://app.example/'), 'header.jwt.value'],
            'origin not in CORS list' => [$spa, $cors('https://other.example'), null],
            'CORS list with other scheme' => [$spa, $cors('http://app.example'), null],
            'CORS wildcard' => [$spa, $cors('*'), 'header.jwt.value'],
            'CORS wildcard, no Origin' => [['HTTP_SEC_FETCH_SITE' => 'cross-site'], $cors('*'), null],
            'CORS disabled' => [$spa, ['cors' => ['enabled' => 0, 'allow_origin_enabled' => 1,
                'allow_origin' => 'https://app.example']], null],
            'CORS allow origin off' => [$spa, ['cors' => ['enabled' => 1, 'allow_origin_enabled' => 0,
                'allow_origin' => 'https://app.example']], null],
        ];
    }

    /**
     * @param array<string,string> $server
     */
    #[DataProvider('corsCookieProvider')]
    public function testCookieJwtWithCorsSettings(array $server, string $origins, ?string $expected): void
    {
        $routeService = $this->routeService($server, [], [
            'cors' => ['enabled' => 1, 'allow_origin_enabled' => 1, 'allow_origin' => $origins],
        ]);

        $this->assertSame($expected, $routeService->getJwtFromRequestHeaderOrCookie());
    }

    /**
     * @return array<string,array{array<string,string>, string, string|null}>
     */
    public static function corsCookieProvider(): array
    {
        $attacker = ['HTTP_SEC_FETCH_SITE' => 'cross-site', 'HTTP_ORIGIN' => 'http://attacker.example'];

        return [
            'CORS wildcard never trusts the cookie' => [$attacker, '*', null],
            'listed origin trusts the cookie' => [$attacker, 'http://attacker.example', 'cookie.jwt.value'],
            'wildcard next to another origin' => [$attacker, 'https://app.example, *', null],
        ];
    }
}
