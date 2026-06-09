<?php

namespace SimpleJwtLoginTests\Feature\DeleteUsers;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use SimpleJWTLogin\ErrorCodes;
use SimpleJwtLoginTests\Feature\FeatureTestCase;

/**
 * Extended feature tests for the DELETE /users endpoint.
 *
 * Covers: feature toggle, auth-code requirement, lookup-by variants
 * (email / user_id / user_login), and post-delete verification.
 */
class ExtendedDeleteTest extends FeatureTestCase
{
    private const JWT_SECRET_KEY = 'test-secret';

    /**
     * @return array<string,mixed>
     */
    private static function baseSettings(): array
    {
        return [
            'allow_authentication'    => true,
            'jwt_payload'             => ['email', 'exp', 'id', 'iss', 'site', 'username'],
            'jwt_auth_ttl'            => 60,
            'jwt_auth_refresh_ttl'    => '20160',
            'auth_ip'                 => '',
            'auth_requires_auth_code' => false,
            'auth_password_base64'    => false,
            'jwt_auth_iss'            => 'tests',
            'decryption_key'          => self::JWT_SECRET_KEY,
            'allow_register'          => true,
            'new_user_profile'        => 'subscriber',
            'register_ip'             => '',
            'register_domain'         => '',
            'require_register_auth'   => false,
            'allow_delete'            => true,
            'require_delete_auth'     => false,
            'delete_ip'               => '',
            'delete_user_by'          => 0,
            'jwt_delete_by_parameter' => 'email',
            'jwt_login_by'            => 0,
            'jwt_login_by_parameter'  => 'email',
        ];
    }

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::updateSimpleJWTOption(self::baseSettings());
    }

    // ─── Feature toggle ───────────────────────────────────────────────────────

    #[TestDox('Delete returns 403 when allow_delete is false')]
    public function testDeleteDisabledReturns403(): void
    {
        self::updateSimpleJWTOption(array_merge(self::baseSettings(), ['allow_delete' => false]));

        try {
            $response = $this->request('DELETE', '/simple-jwt-login/v1/users', [], [
                'Authorization' => 'dummy-jwt',
            ]);

            $this->assertSame(403, $response->getStatusCode());
            $body = json_decode($response->getBody()->getContents(), true);
            $this->assertFalse($body['success']);
            $this->assertSame(ErrorCodes::ERR_DELETE_IS_NOT_ENABLED, $body['data']['error_code']);
        } finally {
            self::updateSimpleJWTOption(self::baseSettings());
        }
    }

    // ─── Auth code on delete ──────────────────────────────────────────────────

    #[TestDox('Delete requires an auth code when require_delete_auth is true')]
    public function testDeleteRequiresAuthCodeWhenConfigured(): void
    {
        [$email, $password, $status] = $this->createUser();
        $this->assertSame(200, $status, 'register failed');
        $jwt = $this->getJWTForUser($email, $password);

        self::updateSimpleJWTOption(array_merge(self::baseSettings(), [
            'require_delete_auth' => true,
            'auth_codes'          => [
                ['code' => 'del-code-abc', 'role' => '', 'expiration_date' => ''],
            ],
        ]));

        try {
            // Without auth code → rejected
            $response = $this->request('DELETE', '/simple-jwt-login/v1/users', [], [
                'Authorization' => $jwt,
            ]);

            $this->assertNotSame(200, $response->getStatusCode());
            $body = json_decode($response->getBody()->getContents(), true);
            $this->assertFalse($body['success']);
            $this->assertSame(ErrorCodes::ERR_AUTH_CODE_REQUIRED, $body['data']['error_code']);
        } finally {
            self::updateSimpleJWTOption(self::baseSettings());
        }
    }

    #[TestDox('Delete succeeds when a valid auth code is provided')]
    public function testDeleteSucceedsWithValidAuthCode(): void
    {
        [$email, $password, $status] = $this->createUser();
        $this->assertSame(200, $status, 'register failed');
        $jwt = $this->getJWTForUser($email, $password);

        self::updateSimpleJWTOption(array_merge(self::baseSettings(), [
            'require_delete_auth' => true,
            'auth_codes'          => [
                ['code' => 'del-code-abc', 'role' => '', 'expiration_date' => ''],
            ],
        ]));

        try {
            $uri     = self::API_URL . '?rest_route=/simple-jwt-login/v1/users&AUTH_KEY=del-code-abc';
            $options = [
                'http_errors' => false,
                'headers'     => ['Authorization' => $jwt, 'Content-Type' => 'application/json'],
            ];
            $response = $this->client->delete($uri, $options);

            $this->assertSame(200, $response->getStatusCode());
        } finally {
            self::updateSimpleJWTOption(self::baseSettings());
        }
    }

    // ─── JWT lookup variants ──────────────────────────────────────────────────

    /**
     * @return array<string,array<string,mixed>>
     */
    public static function lookupByProvider(): array
    {
        // DeleteUserService reads jwt_login_by / jwt_login_by_parameter from LoginSettings,
        // not from DeleteUserSettings. 0=email, 2=user_login.
        return [
            'lookup by email (jwt_login_by=0)' => [0, 'email'],
            'lookup by user_login (jwt_login_by=2)' => [2, 'username'],
        ];
    }

    /**
     * @param int    $jwtLoginBy
     * @param string $jwtLoginByParameter
     */
    #[DataProvider('lookupByProvider')]
    #[TestDox('User can delete themselves regardless of JWT lookup-by strategy')]
    public function testDeleteWithLookupVariant(
        int $jwtLoginBy,
        string $jwtLoginByParameter
    ): void {
        $settings = array_merge(self::baseSettings(), [
            'jwt_login_by'            => $jwtLoginBy,
            'jwt_login_by_parameter'  => $jwtLoginByParameter,
        ]);
        self::updateSimpleJWTOption($settings);

        try {
            [$email, $password, $status] = $this->createUser();
            $this->assertSame(200, $status, 'register failed');
            $jwt = $this->getJWTForUser($email, $password);

            $response = $this->request('DELETE', '/simple-jwt-login/v1/users', [], [
                'Authorization' => $jwt,
            ]);

            $this->assertSame(200, $response->getStatusCode());

            // Verify the user is gone — auth should fail
            [$authStatus] = $this->authUser($email, $password);
            $this->assertSame(401, $authStatus, 'deleted user should not be able to authenticate');
        } finally {
            self::updateSimpleJWTOption(self::baseSettings());
        }
    }

    // ─── Post-delete state ────────────────────────────────────────────────────

    #[TestDox('After deletion the JWT cannot be used for subsequent requests')]
    public function testJwtIsUselessAfterUserDeletion(): void
    {
        [$email, $password, $status] = $this->createUser();
        $this->assertSame(200, $status, 'register failed');
        $jwt = $this->getJWTForUser($email, $password);

        // Delete
        $del = $this->request('DELETE', '/simple-jwt-login/v1/users', [], [
            'Authorization' => $jwt,
        ]);
        $this->assertSame(200, $del->getStatusCode(), 'delete must succeed');

        // Try validate with the same JWT
        $validate = $this->jsonRequest('POST', '/simple-jwt-login/v1/auth/validate', [
            'JWT' => $jwt,
        ]);

        $this->assertNotSame(200, $validate->getStatusCode());
        $body = json_decode($validate->getBody()->getContents(), true);
        $this->assertFalse($body['success']);
    }

    #[TestDox('Re-registering after deletion with the same email succeeds')]
    public function testReRegisterAfterDeletionSucceeds(): void
    {
        $email    = 're_' . uniqid() . '@example.com';
        $password = 'TestPass1!';

        // First registration
        $reg1 = $this->jsonRequest('POST', '/simple-jwt-login/v1/users', [
            'email'    => $email,
            'password' => $password,
        ]);
        $this->assertSame(200, $reg1->getStatusCode(), 'first register failed');

        // Authenticate and delete
        $jwt = $this->getJWTForUser($email, $password);
        $del = $this->request('DELETE', '/simple-jwt-login/v1/users', [], [
            'Authorization' => $jwt,
        ]);
        $this->assertSame(200, $del->getStatusCode(), 'delete failed');

        // Re-register with same email — should succeed since the user no longer exists
        $reg2 = $this->jsonRequest('POST', '/simple-jwt-login/v1/users', [
            'email'    => $email,
            'password' => $password,
        ]);
        $this->assertSame(200, $reg2->getStatusCode(), 're-register after delete must succeed');
        $body = json_decode($reg2->getBody()->getContents(), true);
        $this->assertTrue($body['success']);
    }
}
