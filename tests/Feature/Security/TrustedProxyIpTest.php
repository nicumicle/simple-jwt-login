<?php

namespace SimpleJwtLoginTests\Feature\Security;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use SimpleJWTLogin\ErrorCodes;
use SimpleJwtLoginTests\Feature\FeatureTestCase;

/**
 * With "trust IP headers" on, only the right-most X-Forwarded-For hop (appended by the
 * trusted proxy) identifies the client. Client-IP is passed through untouched by most
 * proxies, so it must never satisfy an IP allowlist.
 */
class TrustedProxyIpTest extends FeatureTestCase
{
    private const ALLOWED_IP = '203.0.113.7';

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        $fixture = json_decode(
            (string) file_get_contents(__DIR__ . '/../../Unit/Modules/Settings/fixtures/stored-settings.json'),
            true
        );
        self::updateSimpleJWTOption(array_replace_recursive($fixture, [
            'general'       => ['security' => ['trust_ip_headers' => true]],
            'authorization' => ['enabled' => '1', 'ip_whitelist' => self::ALLOWED_IP],
        ]));
    }

    /**
     * @return array<string, array{array<string, string>, bool}>
     */
    public static function headerProvider(): array
    {
        return [
            'spoofed Client-IP'                => [['Client-IP' => self::ALLOWED_IP], false],
            'spoofed Client-IP, no proxy hop'  => [['Client-IP' => self::ALLOWED_IP, 'X-Forwarded-For' => 'not-an-ip'], false],
            'X-Forwarded-For hop is not an IP' => [['X-Forwarded-For' => 'not-an-ip'], false],
        ];
        // The X-Forwarded-For hop order is covered by ServerHelperTest: the docker image runs Apache
        // mod_remoteip, which already consumes X-Forwarded-For hops before PHP sees them.
    }

    /**
     * @param array<string, string> $headers
     */
    #[DataProvider('headerProvider')]
    #[TestDox('The /auth IP allowlist with trusted proxy headers: $_dataName')]
    public function testOnlyTheProxyHopSatisfiesTheIpAllowlist(array $headers, bool $allowed): void
    {
        $response = $this->client->request('POST', self::API_URL . '/?rest_route=/simple-jwt-login/v1/auth', [
            'http_errors' => false,
            'headers'     => $headers + ['Content-Type' => 'application/json'],
            // Wrong credentials: the IP check runs first, so an allowed IP gets "wrong credentials" instead.
            'body'        => json_encode(['email' => 'nobody@example.com', 'password' => 'x']),
        ]);
        $body = (array) json_decode((string) $response->getBody(), true);

        $errorCode = isset($body['data']['error_code']) ? $body['data']['error_code'] : null;
        $this->assertSame(
            $allowed ? ErrorCodes::ERR_AUTHENTICATION_WRONG_CREDENTIALS : ErrorCodes::ERR_DELETE_INVALID_CLIENT_IP,
            $errorCode,
            (string) json_encode($body)
        );
    }
}
