<?php

namespace SimpleJwtLoginTests\Feature\ResetPassword;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use SimpleJWTLogin\ErrorCodes;
use SimpleJwtLoginTests\Feature\FeatureTestCase;

/**
 * A password change must cut off every session issued before it:
 * the user's refresh tokens are deleted and older JWTs are rejected,
 * whether the password changed through the plugin or through WordPress itself.
 */
class PasswordChangeRevokesTokensTest extends FeatureTestCase
{
    private const OLD_PASSWORD = 'OldPass1234!';

    private const NEW_PASSWORD = 'NewPass5678!';

    /**
     * @var array<string, mixed>
     */
    private static $settings = [];

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        $fixture = json_decode(
            (string) file_get_contents(__DIR__ . '/../../Unit/Modules/Settings/fixtures/stored-settings.json'),
            true
        );
        self::$settings = array_replace_recursive($fixture, [
            'general'        => [
                'decryption_key'     => 'password-change-secret',
                'request_jwt_header' => 1,
                'api_middleware'     => ['enabled' => true],
            ],
            'authorization'  => [
                'enabled'                => '1',
                'refresh_token_enabled'  => '1',
                'validate_token_enabled' => '1',
                'revoke_on_password_change' => true,
            ],
            'register'       => ['enabled' => true, 'new_user_profile' => 'subscriber'],
            'reset_password' => ['enabled' => true, 'return_jwt' => true],
            'cors'           => ['enabled' => 0],
        ]);
        self::updateSimpleJWTOption(self::$settings);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function passwordChangeProvider(): array
    {
        return [
            'plugin reset password endpoint'   => ['plugin'],
            'WordPress core user profile update' => ['core'],
        ];
    }

    #[DataProvider('passwordChangeProvider')]
    #[TestDox('After a password change through the $channel, a JWT issued before it is rejected')]
    public function testOldJwtIsRejectedAfterPasswordChange(string $channel): void
    {
        $session = $this->loginNewUser();

        $this->changePassword($channel, $session['email'], $session['jwt']);

        $response = $this->validateJwt($session['jwt']);
        $this->assertSame(401, $response['status'], (string) json_encode($response['body']));
        $this->assertFalse($response['body']['success']);
        $this->assertSame(ErrorCodes::ERR_REVOKED_TOKEN, $response['body']['data']['error_code']);
    }

    #[DataProvider('passwordChangeProvider')]
    #[TestDox('After a password change through the $channel, a refresh token issued before it is rejected')]
    public function testOldRefreshTokenIsRejectedAfterPasswordChange(string $channel): void
    {
        $session = $this->loginNewUser();

        $this->changePassword($channel, $session['email'], $session['jwt']);

        $response = $this->jsonRequest('POST', '/simple-jwt-login/v1/auth/refresh', [
            'refresh_token' => $session['refresh_token'],
        ]);
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertNotSame(200, $response->getStatusCode(), (string) json_encode($body));
        $this->assertFalse($body['success']);
    }

    #[DataProvider('passwordChangeProvider')]
    #[TestDox('After a password change through the $channel, a new login gets a JWT that works')]
    public function testNewJwtWorksAfterPasswordChange(string $channel): void
    {
        $session = $this->loginNewUser();

        $this->changePassword($channel, $session['email'], $session['jwt']);
        $newJwt = (string) $this->getJWTForUser($session['email'], self::NEW_PASSWORD);

        $response = $this->validateJwt($newJwt);
        $this->assertSame(200, $response['status'], (string) json_encode($response['body']));
        $this->assertTrue($response['body']['success']);
    }

    #[DataProvider('passwordChangeProvider')]
    #[TestDox('With the option off, a password change through the $channel keeps older JWTs and refresh tokens')]
    public function testOptionOffKeepsSessionsAfterPasswordChange(string $channel): void
    {
        $settings = self::$settings;
        $settings['authorization']['revoke_on_password_change'] = false;
        self::updateSimpleJWTOption($settings);
        try {
            $session = $this->loginNewUser();
            $this->changePassword($channel, $session['email'], $session['jwt']);

            $response = $this->validateJwt($session['jwt']);
            $this->assertSame(200, $response['status'], (string) json_encode($response['body']));
            $refresh = $this->jsonRequest('POST', '/simple-jwt-login/v1/auth/refresh', [
                'refresh_token' => $session['refresh_token'],
            ]);
            $this->assertSame(200, $refresh->getStatusCode(), (string) $refresh->getBody());
        } finally {
            self::updateSimpleJWTOption(self::$settings);
        }
    }

    #[TestDox('A JWT is still accepted when the password did not change')]
    public function testJwtStaysValidWithoutPasswordChange(): void
    {
        $session = $this->loginNewUser();

        $response = $this->validateJwt($session['jwt']);
        $this->assertSame(200, $response['status'], (string) json_encode($response['body']));
    }

    /**
     * @return array{email: string, jwt: string, refresh_token: string}
     */
    private function loginNewUser(): array
    {
        [$email, , $status] = $this->createUser(['user_pass' => self::OLD_PASSWORD]);
        $this->assertSame(200, $status, 'register failed');

        [$authStatus, $authBody] = $this->authUser($email, self::OLD_PASSWORD);
        $this->assertSame(200, $authStatus, 'auth failed');
        $data = json_decode((string) $authBody, true);
        $this->assertArrayHasKey('refresh_token', $data['data'], 'refresh_token missing from auth response');

        // "iat" has a one-second resolution: the change must happen after the second the tokens were issued in.
        sleep(1);

        return [
            'email'         => $email,
            'jwt'           => $data['data']['jwt'],
            'refresh_token' => $data['data']['refresh_token'],
        ];
    }

    private function changePassword(string $channel, string $email, string $jwt): void
    {
        $response = $channel === 'plugin'
            ? $this->jsonRequest('PUT', '/simple-jwt-login/v1/user/reset_password', [
                'email'        => $email,
                'new_password' => self::NEW_PASSWORD,
                'JWT'          => $jwt,
            ])
            : $this->client->request('POST', self::API_URL . '/?rest_route=/wp/v2/users/me', [
                'http_errors' => false,
                'headers'     => $this->authHeader($jwt) + ['Content-Type' => 'application/json'],
                'body'        => json_encode(['password' => self::NEW_PASSWORD]),
            ]);

        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
    }

    /**
     * @return array{status: int, body: array<string, mixed>}
     */
    private function validateJwt(string $jwt): array
    {
        $response = $this->jsonRequest('POST', '/simple-jwt-login/v1/auth/validate', ['JWT' => $jwt]);

        return [
            'status' => $response->getStatusCode(),
            'body'   => (array) json_decode($response->getBody()->getContents(), true),
        ];
    }
}
