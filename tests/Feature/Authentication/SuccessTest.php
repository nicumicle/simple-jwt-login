<?php

namespace SimpleJwtLoginTests\Feature\Authentication;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use SimpleJwtLoginTests\Feature\TestBase;

class SuccessTest extends TestBase
{
    const JWT_SECRET_KEY = "123";

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::updateSimpleJWTOption([
            "allow_authentication" => true,
            "jwt_payload" => ["email","exp","id","iss","site","username"],
            "jwt_auth_ttl" => 60,
            "jwt_auth_refresh_ttl" => "20160",
            "auth_ip" => "",
            "auth_requires_auth_code" => false,
            "auth_password_base64" => false,
            "jwt_auth_iss" => "tests",
            "decryption_key" => self::JWT_SECRET_KEY,
            // Register user
            "allow_register" => true,
            "new_user_profile" => "subscriber",
            "register_ip" => "",
            "register_domain" => "",
            "require_register_auth" => false,
            // Delete user
            "allow_delete" => true,
            "require_delete_auth" => false,
            "delete_ip" => "",
            "delete_user_by" => 0,
            "jwt_delete_by_parameter" => "email",
            // Autologin: We need this for refresh token
            "jwt_login_by" => 0,
            "jwt_login_by_parameter" => "email",
            // Refresh token
            "allow_refresh_token" => 1,
            "refresh_token_key" => "test_refresh_secret_key",
            // Validate token
            "allow_validate_token" => true,
        ]);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function authCredentialProvider(): array
    {
        return [
            'email field, login by email'       => ['email',    'email'],
            'username field, login by username' => ['username', 'username'],
            'login field, login by username'    => ['login',    'username'],
            'login field, login by email'       => ['login',    'email'],
        ];
    }

    #[DataProvider('authCredentialProvider')]
    #[TestDox("User can authenticate using the credential field and password")]
    public function testAuthentication(string $credentialField, string $loginBy): void
    {
        [$email, $password, $statusCode] = $this->registerRandomUser();
        $this->assertSame(200, $statusCode, "Unable to register user");

        $credentialValue = $loginBy === 'email' ? $email : md5($email);

        $uri = self::API_URL . "?rest_route=/simple-jwt-login/v1/auth";
        $result = $this->client->post($uri, [
            'body' => json_encode([
                $credentialField => $credentialValue,
                'password'       => $password,
            ])
        ]);

        $this->assertSame(200, $result->getStatusCode(), "Auth User Failed");
        $responseArray = json_decode($result->getBody()->getContents(), true);
        $this->assertArrayHasKey('success', $responseArray);
        $this->assertTrue($responseArray['success']);
        $this->assertArrayHasKey('data', $responseArray);
        $this->assertArrayHasKey('jwt', $responseArray['data']);

        [$statusCode] = $this->deleteUser($responseArray['data']['jwt']);
        $this->assertSame(200, $statusCode, "unable to delete the user");
    }

    #[TestDox("User can refresh a valid JWT")]
    public function testRefreshToken(): void
    {
        [$email, $password, $statusCode] = $this->registerRandomUser();
        $this->assertSame(200, $statusCode, "Unable to register user");

        [$statusCode, $responseContents] = $this->authUser($email, $password);
        $this->assertSame(200, $statusCode, "Auth User Failed");

        $responseArray = json_decode($responseContents, true);
        $jwt = $responseArray['data']['jwt'];
        $this->assertArrayHasKey('refresh_token', $responseArray['data'], "Refresh token should be present in auth response");
        $refreshToken = $responseArray['data']['refresh_token'];

        $refreshResponse = $this->client->post(self::API_URL . "?rest_route=/simple-jwt-login/v1/auth/refresh", [
            'body' => json_encode(['refresh_token' => $refreshToken]),
        ]);

        $this->assertSame(200, $refreshResponse->getStatusCode(), "unable to refresh token");
        $refreshResponseArray = json_decode($refreshResponse->getBody()->getContents(), true);
        $this->assertTrue($refreshResponseArray['success']);
        $this->assertArrayHasKey('data', $refreshResponseArray);
        $this->assertArrayHasKey('jwt', $refreshResponseArray['data']);
        $this->assertArrayHasKey('refresh_token', $refreshResponseArray['data'], "New refresh token should be present in refresh response");

        [$statusCode] = $this->deleteUser($jwt);
        $this->assertSame(200, $statusCode, "unable to delete the user");
    }

    #[TestDox("User can validate a JWT")]
    public function testValidateToken(): void
    {
        [$email, $password, $statusCode] = $this->registerRandomUser();
        $this->assertSame(200, $statusCode, "Unable to register user");

        [$statusCode, $responseContents] = $this->authUser($email, $password);
        $this->assertSame(200, $statusCode, "Auth User Failed");

        $responseArray = json_decode($responseContents, true);
        $jwt = $responseArray['data']['jwt'];

        $validateResponse = $this->client->post(self::API_URL . "?rest_route=/simple-jwt-login/v1/auth/validate", [
            'body' => json_encode(['JWT' => $jwt]),
        ]);

        $this->assertSame(200, $validateResponse->getStatusCode(), "unable to validate token");
        $validateRespArr = json_decode($validateResponse->getBody()->getContents(), true);
        $this->assertTrue($validateRespArr['success']);
        $this->assertArrayHasKey('data', $validateRespArr);
        $this->assertArrayHasKey('user', $validateRespArr['data']);
        $this->assertArrayHasKey('jwt', $validateRespArr['data']);
        $this->assertArrayHasKey('roles', $validateRespArr['data']);
        $this->assertSame($email, $validateRespArr['data']['user']['user_email']);

        [$statusCode] = $this->deleteUser($jwt);
        $this->assertSame(200, $statusCode, "unable to delete the user");
    }
}
