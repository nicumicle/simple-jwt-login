<?php

namespace SimpleJwtLoginTests\WP\ResetPassword;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Libraries\JWT\JWT;
use SimpleJWTLogin\Modules\Settings\LoginSettings;
use SimpleJWTLogin\Modules\Settings\ResetPasswordSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJwtLoginTests\WP\WPTestCase;

/**
 * Integration tests for the Reset Password endpoint.
 *
 * Routes:
 *   POST /simple-jwt-login/v1/user/reset_password — request a password reset email
 *   PUT  /simple-jwt-login/v1/user/reset_password — change the user's password
 *
 * Service flow tested here:
 *   1. isResetPasswordEnabled()
 *   2. isAuthKeyRequired() + validateAuthKey()
 *   3. POST: validateSendResetPassword() → user lookup → flow-specific email dispatch
 *   4. PUT:  validateChangePassword() → getUser() via code or JWT → resetPassword()
 */
class ResetPasswordTest extends WPTestCase
{
    private const JWT_SECRET = 'reset-password-test-secret';
    private const ROUTE      = '/simple-jwt-login/v1/user/reset_password';
    private const AUTH_KEY   = 'AUTH_KEY';
    private const AUTH_CODE  = 'VALID_RESET_CODE';

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        static::configurePlugin(static::baseConfig());
    }

    public static function tearDownAfterClass(): void
    {
        delete_option(SimpleJWTLoginSettings::OPTIONS_KEY);
        parent::tearDownAfterClass();
    }

    /**
     * @param array<string,mixed> $overrides
     * @return array<string,mixed>
     */
    private static function baseConfig(array $overrides = []): array
    {
        return array_merge([
            'allow_reset_password'              => true,
            'reset_password_requires_auth_code' => false,
            'jwt_reset_password_flow'           => ResetPasswordSettings::FLOW_JUST_SAVE_IN_DB,
            'reset_password_jwt'                => false,
            'decryption_key'                    => self::JWT_SECRET,
            'jwt_auth_iss'                      => 'tests',
            'jwt_login_by'                      => LoginSettings::JWT_LOGIN_BY_EMAIL,
            'jwt_login_by_parameter'            => 'email',
            'jwt_payload'                       => ['email', 'exp', 'id', 'iss', 'site', 'username'],
            'allow_autologin'                   => false,
            'allow_authentication'              => false,
            'allow_register'                    => false,
            'allow_delete'                      => false,
        ], $overrides);
    }

    private function makeJwt(array $payload): string
    {
        return JWT::encode(
            array_merge(['exp' => time() + 3600], $payload),
            self::JWT_SECRET,
            'HS256'
        );
    }

    private function jwtForEmail(string $email): string
    {
        return $this->makeJwt(['email' => $email]);
    }

    // ─── Feature disabled ─────────────────────────────────────────────────────

    #[TestDox('POST returns ERR_RESET_PASSWORD_IS_NOT_ALLOWED when feature is disabled')]
    public function testPostResetPasswordDisabledReturnsError(): void
    {
        static::configurePlugin(static::baseConfig(['allow_reset_password' => false]));

        $response = $this->request('POST', self::ROUTE, ['email' => 'test@example.com']);

        $data = $response->get_data();
        $this->assertFalse($data['success']);
        $this->assertSame(ErrorCodes::ERR_RESET_PASSWORD_IS_NOT_ALLOWED, $data['data']['errorCode']);
    }

    #[TestDox('PUT returns ERR_RESET_PASSWORD_IS_NOT_ALLOWED when feature is disabled')]
    public function testPutResetPasswordDisabledReturnsError(): void
    {
        static::configurePlugin(static::baseConfig(['allow_reset_password' => false]));

        $response = $this->request('PUT', self::ROUTE, [
            'email'        => 'test@example.com',
            'new_password' => 'newpass',
            'code'         => 'somecode',
        ]);

        $data = $response->get_data();
        $this->assertFalse($data['success']);
        $this->assertSame(ErrorCodes::ERR_RESET_PASSWORD_IS_NOT_ALLOWED, $data['data']['errorCode']);
    }

    // ─── Auth key validation ──────────────────────────────────────────────────

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function invalidAuthKeyProvider(): array
    {
        return [
            'no AUTH_KEY param provided' => [
                'params'         => [],
                'useExpiredCode' => false,
            ],
            'wrong AUTH_KEY value' => [
                'params'         => [self::AUTH_KEY => 'WRONG_CODE'],
                'useExpiredCode' => false,
            ],
            'expired auth code' => [
                'params'         => [self::AUTH_KEY => 'EXPIRED_CODE'],
                'useExpiredCode' => true,
            ],
        ];
    }

    #[DataProvider('invalidAuthKeyProvider')]
    #[TestDox('POST returns ERR_RESET_PASSWORD_INVALID_AUTH_KEY for invalid auth key')]
    public function testPostInvalidAuthKeyReturnsError(array $params, bool $useExpiredCode): void
    {
        $authCodes = $useExpiredCode
            ? [['code' => 'EXPIRED_CODE', 'role' => '', 'expiration_date' => '2000-01-01']]
            : [['code' => self::AUTH_CODE, 'role' => '', 'expiration_date' => '']];

        static::configurePlugin(static::baseConfig([
            'reset_password_requires_auth_code' => true,
            'auth_codes'                        => $authCodes,
            'auth_code_key'                     => self::AUTH_KEY,
        ]));

        [$email] = $this->createUser();

        $response = $this->request('POST', self::ROUTE, array_merge(
            ['email' => $email],
            $params
        ));

        $data = $response->get_data();
        $this->assertFalse($data['success']);
        $this->assertSame(ErrorCodes::ERR_RESET_PASSWORD_INVALID_AUTH_KEY, $data['data']['errorCode']);
    }

    #[DataProvider('invalidAuthKeyProvider')]
    #[TestDox('PUT returns ERR_RESET_PASSWORD_INVALID_AUTH_KEY for invalid auth key')]
    public function testPutInvalidAuthKeyReturnsError(array $params, bool $useExpiredCode): void
    {
        $authCodes = $useExpiredCode
            ? [['code' => 'EXPIRED_CODE', 'role' => '', 'expiration_date' => '2000-01-01']]
            : [['code' => self::AUTH_CODE, 'role' => '', 'expiration_date' => '']];

        static::configurePlugin(static::baseConfig([
            'reset_password_requires_auth_code' => true,
            'auth_codes'                        => $authCodes,
            'auth_code_key'                     => self::AUTH_KEY,
        ]));

        [$email] = $this->createUser();

        $response = $this->request('PUT', self::ROUTE, array_merge(
            ['email' => $email, 'new_password' => 'newpass', 'code' => 'anycode'],
            $params
        ));

        $data = $response->get_data();
        $this->assertFalse($data['success']);
        $this->assertSame(ErrorCodes::ERR_RESET_PASSWORD_INVALID_AUTH_KEY, $data['data']['errorCode']);
    }

    #[TestDox('POST proceeds past auth key check when a valid AUTH_KEY is provided')]
    public function testPostValidAuthKeyAllowsRequest(): void
    {
        static::configurePlugin(static::baseConfig([
            'reset_password_requires_auth_code' => true,
            'auth_codes'                        => [
                ['code' => self::AUTH_CODE, 'role' => '', 'expiration_date' => ''],
            ],
            'auth_code_key' => self::AUTH_KEY,
        ]));

        [$email] = $this->createUser();

        $response = $this->request('POST', self::ROUTE, [
            'email'        => $email,
            self::AUTH_KEY => self::AUTH_CODE,
        ]);

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
    }

    // ─── POST: Send Reset Password ────────────────────────────────────────────

    #[TestDox('POST returns ERR_MISSING_NEW_PASSWORD_FOR_RESET_PASSWORD when email is absent')]
    public function testPostMissingEmailReturnsError(): void
    {
        static::configurePlugin(static::baseConfig());

        $response = $this->request('POST', self::ROUTE, []);

        $data = $response->get_data();
        $this->assertFalse($data['success']);
        $this->assertSame(
            ErrorCodes::ERR_MISSING_NEW_PASSWORD_FOR_RESET_PASSWORD,
            $data['data']['errorCode']
        );
    }

    #[TestDox('POST returns ERR_USER_NOT_FOUND_FOR_RESET_PASSWORD when email has no matching WP user')]
    public function testPostUnknownEmailReturnsUserNotFoundError(): void
    {
        static::configurePlugin(static::baseConfig());

        $response = $this->request('POST', self::ROUTE, [
            'email' => 'ghost-' . time() . '@nowhere.invalid',
        ]);

        $data = $response->get_data();
        $this->assertFalse($data['success']);
        $this->assertSame(
            ErrorCodes::ERR_USER_NOT_FOUND_FOR_RESET_PASSWORD,
            $data['data']['errorCode']
        );
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function sendResetPasswordFlowProvider(): array
    {
        return [
            'FLOW_JUST_SAVE_IN_DB saves the key without sending email' => [
                'flow'    => ResetPasswordSettings::FLOW_JUST_SAVE_IN_DB,
                'message' => 'The Code has been saved into the database.',
            ],
            'FLOW_SEND_DEFAULT_WP_EMAIL triggers the WordPress reset email' => [
                'flow'    => ResetPasswordSettings::FLOW_SEND_DEFAULT_WP_EMAIL,
                'message' => 'Reset password email has been sent.',
            ],
        ];
    }

    #[DataProvider('sendResetPasswordFlowProvider')]
    #[TestDox('POST returns success with correct message for each built-in flow type')]
    public function testPostSuccessForBuiltInFlow(int $flow, string $message): void
    {
        static::configurePlugin(static::baseConfig([
            'jwt_reset_password_flow' => $flow,
        ]));

        [$email] = $this->createUser();

        $response = $this->request('POST', self::ROUTE, ['email' => $email]);

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertStringContainsString($message, $data['message']);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function customEmailTypeProvider(): array
    {
        return [
            'custom email — plain text' => [
                'emailType' => ResetPasswordSettings::EMAIL_TYPE_PLAIN_TEXT,
            ],
            'custom email — HTML' => [
                'emailType' => ResetPasswordSettings::EMAIL_TYPE_HTML,
            ],
        ];
    }

    #[DataProvider('customEmailTypeProvider')]
    #[TestDox('POST FLOW_SEND_CUSTOM_EMAIL returns success for plain text and HTML email types')]
    public function testPostCustomEmailFlowReturnsSuccess(int $emailType): void
    {
        static::configurePlugin(static::baseConfig([
            'jwt_reset_password_flow'       => ResetPasswordSettings::FLOW_SEND_CUSTOM_EMAIL,
            'jwt_email_subject'             => 'Reset your password',
            'jwt_reset_password_email_body' => base64_encode('Click here to reset: {{CODE}}'),
            'jwt_email_type'                => $emailType,
        ]));

        [$email] = $this->createUser();

        $response = $this->request('POST', self::ROUTE, ['email' => $email]);

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertStringContainsString('Reset password email has been sent.', $data['message']);
    }

    // ─── PUT: Change Password — validation failures ───────────────────────────

    #[TestDox('PUT returns ERR_MISSING_EMAIL_FOR_CHANGE_PASSWORD when email is absent')]
    public function testPutMissingEmailReturnsError(): void
    {
        static::configurePlugin(static::baseConfig());

        $response = $this->request('PUT', self::ROUTE, [
            'new_password' => 'newpassword',
            'code'         => 'somecode',
        ]);

        $data = $response->get_data();
        $this->assertFalse($data['success']);
        $this->assertSame(
            ErrorCodes::ERR_MISSING_EMAIL_FOR_CHANGE_PASSWORD,
            $data['data']['errorCode']
        );
    }

    #[TestDox('PUT returns ERR_MISSING_NEW_PASSWORD_FOR_CHANGE_PASSWORD when new_password is absent')]
    public function testPutMissingNewPasswordReturnsError(): void
    {
        static::configurePlugin(static::baseConfig());

        [$email] = $this->createUser();

        $response = $this->request('PUT', self::ROUTE, [
            'email' => $email,
            'code'  => 'somecode',
        ]);

        $data = $response->get_data();
        $this->assertFalse($data['success']);
        $this->assertSame(
            ErrorCodes::ERR_MISSING_NEW_PASSWORD_FOR_CHANGE_PASSWORD,
            $data['data']['errorCode']
        );
    }

    #[TestDox('PUT returns ERR_MISSING_CODE_FOR_CHANGE_PASSWORD when JWT is not allowed and code is absent')]
    public function testPutMissingCodeWhenJwtNotAllowedReturnsError(): void
    {
        static::configurePlugin(static::baseConfig([
            'reset_password_jwt' => false,
        ]));

        [$email] = $this->createUser();

        $response = $this->request('PUT', self::ROUTE, [
            'email'        => $email,
            'new_password' => 'newpassword',
        ]);

        $data = $response->get_data();
        $this->assertFalse($data['success']);
        $this->assertSame(
            ErrorCodes::ERR_MISSING_CODE_FOR_CHANGE_PASSWORD,
            $data['data']['errorCode']
        );
    }

    #[TestDox('PUT returns ERR_INVALID_RESET_PASSWORD_CODE when the reset code is not valid')]
    public function testPutInvalidCodeReturnsError(): void
    {
        static::configurePlugin(static::baseConfig([
            'reset_password_jwt' => false,
        ]));

        [$email] = $this->createUser();

        $response = $this->request('PUT', self::ROUTE, [
            'email'        => $email,
            'new_password' => 'newpassword',
            'code'         => 'totally-invalid-code-' . time(),
        ]);

        $data = $response->get_data();
        $this->assertFalse($data['success']);
        $this->assertSame(
            ErrorCodes::ERR_INVALID_RESET_PASSWORD_CODE,
            $data['data']['errorCode']
        );
    }

    // ─── PUT: Change Password — JWT path failures ─────────────────────────────

    #[TestDox('PUT returns ERR_MISSING_JWT_AUTH_VALIDATE when JWT is allowed but no JWT is provided')]
    public function testPutJwtAllowedButMissingJwtReturnsError(): void
    {
        static::configurePlugin(static::baseConfig([
            'reset_password_jwt' => true,
        ]));

        [$email] = $this->createUser();

        $response = $this->request('PUT', self::ROUTE, [
            'email'        => $email,
            'new_password' => 'newpassword',
        ]);

        $data = $response->get_data();
        $this->assertFalse($data['success']);
        $this->assertSame(
            ErrorCodes::ERR_MISSING_JWT_AUTH_VALIDATE,
            $data['data']['errorCode']
        );
    }

    #[TestDox('PUT returns error when the JWT email does not match the request email')]
    public function testPutJwtEmailMismatchReturnsError(): void
    {
        static::configurePlugin(static::baseConfig([
            'reset_password_jwt' => true,
        ]));

        [$email] = $this->createUser();
        $wrongEmailJwt = $this->jwtForEmail('different-' . $email);

        $response = $this->request('PUT', self::ROUTE, [
            'email'        => $email,
            'new_password' => 'newpassword',
            'JWT'          => $wrongEmailJwt,
        ]);

        $data = $response->get_data();
        $this->assertFalse($data['success']);
    }

    // ─── PUT: Change Password — success paths ────────────────────────────────

    #[TestDox('PUT successfully changes password when a valid WP reset code is provided')]
    public function testPutChangesPasswordWithValidCode(): void
    {
        static::configurePlugin(static::baseConfig([
            'reset_password_jwt' => false,
        ]));

        [, , $userId] = $this->createUser();
        $user = get_user_by('id', $userId);
        $this->assertNotFalse($user, 'Pre-condition: user must exist');

        $key = get_password_reset_key($user);
        $this->assertNotWPError($key, 'Pre-condition: reset key must be generated successfully');

        $response = $this->request('PUT', self::ROUTE, [
            'email'        => $user->user_email,
            'new_password' => 'NewSecurePassword123!',
            'code'         => $key,
        ]);

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertStringContainsString('User Password has been changed.', $data['message']);
    }

    #[TestDox('PUT successfully changes password when a valid JWT is provided')]
    public function testPutChangesPasswordWithValidJwt(): void
    {
        static::configurePlugin(static::baseConfig([
            'reset_password_jwt' => true,
        ]));

        [$email] = $this->createUser();
        $jwt = $this->jwtForEmail($email);

        $response = $this->request('PUT', self::ROUTE, [
            'email'        => $email,
            'new_password' => 'NewSecurePassword123!',
            'JWT'          => $jwt,
        ]);

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertStringContainsString('User Password has been changed.', $data['message']);
    }

    #[TestDox('PUT successfully changes password when new_password is base64-encoded')]
    public function testPutChangesPasswordWithBase64EncodedPassword(): void
    {
        static::configurePlugin(static::baseConfig([
            'reset_password_jwt'   => true,
            'auth_password_base64' => true,
        ]));

        [$email] = $this->createUser();
        $jwt = $this->jwtForEmail($email);

        $response = $this->request('PUT', self::ROUTE, [
            'email'        => $email,
            'new_password' => base64_encode('NewSecurePassword123!'),
            'JWT'          => $jwt,
        ]);

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
    }

    #[TestDox('PUT accepts a JWT passed via the Authorization: Bearer header')]
    public function testPutJwtViaAuthorizationHeaderChangesPassword(): void
    {
        static::configurePlugin(static::baseConfig([
            'reset_password_jwt' => true,
        ]));

        [$email] = $this->createUser();
        $jwt = $this->jwtForEmail($email);

        $response = $this->request('PUT', self::ROUTE, [
            'email'        => $email,
            'new_password' => 'NewSecurePassword123!',
        ], $this->authHeader($jwt));

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
    }

    // ─── Response shape ───────────────────────────────────────────────────────

    #[TestDox('POST success response contains success=true and a message')]
    public function testPostSuccessResponseShape(): void
    {
        static::configurePlugin(static::baseConfig());

        [$email] = $this->createUser();

        $response = $this->request('POST', self::ROUTE, ['email' => $email]);

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('message', $data);
        $this->assertNotEmpty($data['message']);
    }

    #[TestDox('PUT success response contains success=true and a message')]
    public function testPutSuccessResponseShape(): void
    {
        static::configurePlugin(static::baseConfig([
            'reset_password_jwt' => true,
        ]));

        [$email] = $this->createUser();
        $jwt = $this->jwtForEmail($email);

        $response = $this->request('PUT', self::ROUTE, [
            'email'        => $email,
            'new_password' => 'NewSecurePassword123!',
            'JWT'          => $jwt,
        ]);

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('message', $data);
        $this->assertNotEmpty($data['message']);
    }
}
