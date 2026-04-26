<?php

namespace SimpleJwtLoginTests\WP\Autologin;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Libraries\JWT\JWT;
use SimpleJWTLogin\Modules\Settings\LoginSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJwtLoginTests\WP\WPTestCase;

/**
 * JWT issuer (iss) restriction on the autologin endpoint.
 *
 * Config: login_iss = 'issuer-a,issuer-b' — only these two issuers are permitted.
 */
class AutologinIssRestrictionTest extends WPTestCase
{
    private const JWT_SECRET = 'autologin-iss-secret';
    private const ROUTE      = '/simple-jwt-login/v1/autologin';

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        static::configurePlugin([
            'allow_autologin'         => true,
            'jwt_login_by'            => LoginSettings::JWT_LOGIN_BY_EMAIL,
            'jwt_login_by_parameter'  => 'email',
            'decryption_key'          => self::JWT_SECRET,
            'redirect'                => LoginSettings::NO_REDIRECT,
            'require_login_auth'      => false,
            'login_ip'                => '',
            'login_iss'               => 'issuer-a,issuer-b',
            'allow_authentication'    => true,
            'jwt_payload'             => ['email', 'exp'],
            'jwt_auth_ttl'            => 60,
        ]);
    }

    public static function tearDownAfterClass(): void
    {
        delete_option(SimpleJWTLoginSettings::OPTIONS_KEY);
        parent::tearDownAfterClass();
    }

    private function makeJwt(array $payload): string
    {
        return JWT::encode($payload, self::JWT_SECRET, 'HS256');
    }

    // ─── ISS restriction error scenarios ─────────────────────────────────────

    #[DataProvider('blockedIssProvider')]
    #[TestDox('Autologin rejects JWTs whose issuer is not in the allowlist')]
    public function testBlockedIss(array $payload): void
    {
        $response = $this->request('GET', self::ROUTE, [
            'JWT' => $this->makeJwt($payload),
        ]);

        $this->assertSame(400, $response->get_status());
        $data = $response->get_data();
        $this->assertFalse($data['success']);
        $this->assertSame(ErrorCodes::ERR_INVALID_IIS_LOGIN, $data['data']['errorCode']);
    }

    public static function blockedIssProvider(): array
    {
        return [
            'JWT issuer not in allowlist' => [
                'payload' => ['email' => 'x@x.com', 'iss' => 'evil-issuer'],
            ],
            'JWT has no issuer claim' => [
                'payload' => ['email' => 'x@x.com'],
            ],
            'JWT issuer is empty string' => [
                'payload' => ['email' => 'x@x.com', 'iss' => ''],
            ],
        ];
    }

    // ─── ISS restriction success scenarios ───────────────────────────────────

    #[DataProvider('allowedIssProvider')]
    #[TestDox('Autologin succeeds when the JWT issuer is in the configured allowlist')]
    public function testAllowedIss(string $issuer): void
    {
        [$email] = $this->createUser();

        $response = $this->request('GET', self::ROUTE, [
            'JWT' => $this->makeJwt(['email' => $email, 'iss' => $issuer]),
        ]);

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertSame('User was logged in.', $data['message']);
    }

    public static function allowedIssProvider(): array
    {
        return [
            'first issuer in allowlist'  => ['issuer-a'],
            'second issuer in allowlist' => ['issuer-b'],
        ];
    }
}
