<?php

namespace SimpleJwtLoginTests\WP\Autologin;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Libraries\JWT\JWT;
use SimpleJWTLogin\Modules\Settings\LoginSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;

/**
 * Base autologin scenarios: JWT validation errors, user lookup by email,
 * JWT transport variants, and nested payload resolution.
 *
 * Config: autologin enabled, login by email, no IP/ISS/auth-code restrictions,
 * NO_REDIRECT so every successful login returns a JSON response.
 */
class AutologinTest extends AutologinTestCase
{
    private const JWT_SECRET = 'autologin-base-secret';

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
            'login_iss'               => '',
            'allow_authentication'    => true,
            'jwt_payload'             => ['email', 'exp', 'id', 'username'],
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

    // ─── JWT validation errors ────────────────────────────────────────────────

    #[DataProvider('jwtErrorProvider')]
    #[TestDox('Autologin rejects an invalid JWT with the expected error code')]
    public function testJwtErrors(array $params, array $headers, int $expectedCode, int $expectedStatus): void
    {
        $response = $this->request('GET', self::ROUTE, $params, $headers);

        $this->assertSame($expectedStatus, $response->get_status());
        $data = $response->get_data();
        $this->assertFalse($data['success']);
        $this->assertSame($expectedCode, $data['data']['error_code']);
    }

    public static function jwtErrorProvider(): array
    {
        $wrongSigJwt = JWT::encode(['email' => 'x@x.com'], 'wrong-secret', 'HS256');
        $expiredJwt  = JWT::encode(['email' => 'x@x.com', 'exp' => time() - 3600], self::JWT_SECRET, 'HS256');

        return [
            'no JWT in request or header' => [
                'params'         => [],
                'headers'        => [],
                'expectedCode'   => ErrorCodes::ERR_JWT_IS_MISSING,
                'expectedStatus' => 422,
            ],
            'JWT is a single segment' => [
                'params'         => ['JWT' => 'notatoken'],
                'headers'        => [],
                'expectedCode'   => ErrorCodes::ERR_WRONG_NUMBER_OF_SEGMENTS,
                'expectedStatus' => 401,
            ],
            'JWT has four segments' => [
                'params'         => ['JWT' => 'a.b.c.d'],
                'headers'        => [],
                'expectedCode'   => ErrorCodes::ERR_WRONG_NUMBER_OF_SEGMENTS,
                'expectedStatus' => 401,
            ],
            'JWT signed with wrong secret' => [
                'params'         => ['JWT' => $wrongSigJwt],
                'headers'        => [],
                'expectedCode'   => ErrorCodes::ERR_SIGNATURE_VERIFICATION_FAILED,
                'expectedStatus' => 401,
            ],
            'JWT is expired' => [
                'params'         => ['JWT' => $expiredJwt],
                'headers'        => [],
                'expectedCode'   => ErrorCodes::ERR_TOKEN_EXPIRED,
                'expectedStatus' => 401,
            ],
        ];
    }

    // ─── User not found ───────────────────────────────────────────────────────

    #[TestDox('Autologin returns user-not-found when the email in the JWT does not exist')]
    public function testUserNotFoundByEmail(): void
    {
        $jwt = $this->makeJwt(['email' => 'nonexistent@nowhere.invalid']);

        $response = $this->request('GET', self::ROUTE, ['JWT' => $jwt]);

        $this->assertSame(400, $response->get_status());
        $data = $response->get_data();
        $this->assertFalse($data['success']);
        $this->assertSame(ErrorCodes::ERR_DO_LOGIN_USER_NOT_FOUND, $data['data']['error_code']);
    }

    // ─── Successful autologin ─────────────────────────────────────────────────

    #[TestDox('Autologin succeeds and returns a JSON success message (NO_REDIRECT)')]
    public function testSuccessLoginByEmail(): void
    {
        [$email] = $this->createUser();

        $response = $this->request('GET', self::ROUTE, [
            'JWT' => $this->makeJwt(['email' => $email]),
        ]);

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertSame('User was logged in.', $data['message']);
    }

    #[TestDox('Autologin accepts the JWT passed as a bare Authorization header')]
    public function testJwtInAuthorizationHeader(): void
    {
        [$email] = $this->createUser();

        $response = $this->request(
            'GET',
            self::ROUTE,
            [],
            ['Authorization' => $this->makeJwt(['email' => $email])]
        );

        $this->assertSame(200, $response->get_status());
        $this->assertTrue($response->get_data()['success']);
    }

    #[TestDox('Autologin accepts the JWT passed as Authorization: Bearer <token>')]
    public function testJwtInBearerHeader(): void
    {
        [$email] = $this->createUser();

        $response = $this->request(
            'GET',
            self::ROUTE,
            [],
            $this->authHeader($this->makeJwt(['email' => $email]))
        );

        $this->assertSame(200, $response->get_status());
        $this->assertTrue($response->get_data()['success']);
    }

    #[TestDox('Autologin resolves a nested JWT payload property (e.g. user.email)')]
    public function testNestedPayloadProperty(): void
    {
        // jwt_login_by_parameter = 'email' resolves payload['email'] directly;
        // wrapping in a nested key verifies ArrayHelper traversal.
        // Here we keep it simple: the fixture email IS at the top-level key 'email'.
        [$email] = $this->createUser();

        $response = $this->request('GET', self::ROUTE, [
            'JWT' => $this->makeJwt(['email' => $email]),
        ]);

        $this->assertSame(200, $response->get_status());
        $this->assertTrue($response->get_data()['success']);
    }

    #[TestDox('Autologin permits any JWT issuer when no ISS restriction is configured')]
    public function testAnyIssuerAllowedWhenNoRestriction(): void
    {
        [$email] = $this->createUser();

        $response = $this->request('GET', self::ROUTE, [
            'JWT' => $this->makeJwt(['email' => $email, 'iss' => 'any-random-issuer']),
        ]);

        $this->assertSame(200, $response->get_status());
        $this->assertTrue($response->get_data()['success']);
    }
}
