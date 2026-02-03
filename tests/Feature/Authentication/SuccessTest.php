<?php

namespace SimpleJwtLoginTests\Feature\Authentication;

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
        ]);
    }

    #[TestDox("User can authenticate with email and password")]
    public function testAuthenticationEmail()
    {
        // Register random user
        list ($email, $password, $statusCode, $response) = $this->registerRandomUser();

        $this->assertSame(200, $statusCode, "Unable to register user");

        // Auth new USer
        list ($statusCode, $responseContents) = $this->authUser($email, $password);

        $this->assertSame(
            200,
            $statusCode,
            "Auth User Failed"
        );
        $responseArray = json_decode($responseContents, true);
        $this->assertArrayHasKey('success', $responseArray);
        $this->assertTrue($responseArray['success']);
        $this->assertArrayHasKey('data', $responseArray);
        $this->assertArrayHasKey('jwt', $responseArray['data']);
        $jwt = $responseArray['data']['jwt'];
        // Cleanup
        list($statusCode, $response) = $this->deleteUser($jwt);
        $this->assertSame(200, $statusCode, "unable to delete the user");
    }

    #[TestDox("User can authenticate with username and password")]
    public function testAuthenticationUsername()
    {
        // Register random user
        list ($email, $password, $statusCode, $response) = $this->registerRandomUser();

        $this->assertSame(200, $statusCode, "Unable to register user");

       // Auth new USer
        $uri = self::API_URL . "?rest_route=/simple-jwt-login/v1/auth";
        $result = $this->client->post($uri, [
           'body' => json_encode([
               'username' => md5($email),
               'password' => $password,
           ])
        ]);

        $this->assertSame(
            200,
            $result->getStatusCode(),
            "Auth User Failed"
        );
        $responseArray = json_decode($result->getBody()->getContents(), true);
        $this->assertArrayHasKey('success', $responseArray);
        $this->assertTrue($responseArray['success']);
        $this->assertArrayHasKey('data', $responseArray);
        $this->assertArrayHasKey('jwt', $responseArray['data']);
        $jwt = $responseArray['data']['jwt'];
        // Cleanup
        list($statusCode, $response) = $this->deleteUser($jwt);
        $this->assertSame(200, $statusCode, "unable to delete the user");
    }


    #[TestDox("User can authenticate with login(username) and password")]
    public function testAuthenticationLogin()
    {
        // Register random user
        list ($email, $password, $statusCode, $response) = $this->registerRandomUser();

        $this->assertSame(200, $statusCode, "Unable to register user");

        // Auth new USer
        $uri = self::API_URL . "?rest_route=/simple-jwt-login/v1/auth";
        $result = $this->client->post($uri, [
            'body' => json_encode([
                'login' => md5($email),
                'password' => $password,
            ])
        ]);

        $this->assertSame(
            200,
            $result->getStatusCode(),
            "Auth User Failed"
        );
        $responseArray = json_decode($result->getBody()->getContents(), true);
        $this->assertArrayHasKey('success', $responseArray);
        $this->assertTrue($responseArray['success']);
        $this->assertArrayHasKey('data', $responseArray);
        $this->assertArrayHasKey('jwt', $responseArray['data']);
        $jwt = $responseArray['data']['jwt'];
        // Cleanup
        list($statusCode, $response) = $this->deleteUser($jwt);
        $this->assertSame(200, $statusCode, "unable to delete the user");
    }

    #[TestDox("User can authenticate with login(email) and password")]
    public function testAuthenticationLoginEmail()
    {
        // Register random user
        list ($email, $password, $statusCode, $response) = $this->registerRandomUser();

        $this->assertSame(200, $statusCode, "Unable to register user");

        // Auth new USer
        $uri = self::API_URL . "?rest_route=/simple-jwt-login/v1/auth";
        $result = $this->client->post($uri, [
            'body' => json_encode([
                'login' => $email,
                'password' => $password,
            ])
        ]);

        $this->assertSame(
            200,
            $result->getStatusCode(),
            "Auth User Failed"
        );
        $responseArray = json_decode($result->getBody()->getContents(), true);
        $this->assertArrayHasKey('success', $responseArray);
        $this->assertTrue($responseArray['success']);
        $this->assertArrayHasKey('data', $responseArray);
        $this->assertArrayHasKey('jwt', $responseArray['data']);
        $jwt = $responseArray['data']['jwt'];
        // Cleanup
        list($statusCode, $response) = $this->deleteUser($jwt);
        $this->assertSame(200, $statusCode, "unable to delete the user");
    }


    #[TestDox("User can refresh a valid JWT")]
    public function testRefreshToken()
    {
        // Register random user
        list ($email, $password, $statusCode, $response ) = $this->registerRandomUser();

        $this->assertSame(200, $statusCode, "Unable to register user");

        // Auth new USer
        list ($statusCode, $responseContents) = $this->authUser($email, $password);

        $this->assertSame(
            200,
            $statusCode,
            "Auth User Failed"
        );
        $responseArray = json_decode($responseContents, true);
        $jwt = $responseArray['data']['jwt'];

        $refreshResponse = $this->client->post(self::API_URL . "?rest_route=/simple-jwt-login/v1/auth/refresh", [
            'body' => json_encode([
                'JWT' => $jwt
            ]),
        ]);

        $this->assertSame(200, $refreshResponse->getStatusCode(), "unable to refresh token");
        $refreshResponseArray = json_decode($refreshResponse->getBody()->getContents(), true);

        $this->assertTrue($refreshResponseArray['success']);
        $this->assertArrayHasKey('data', $refreshResponseArray);
        $this->assertArrayHasKey('jwt', $refreshResponseArray['data']);

        // Cleanup
        list($statusCode, $response) = $this->deleteUser($jwt);
        $this->assertSame(200, $statusCode, "unable to delete the user");
    }

    #[TestDox("User can validate a JWT")]
    public function testValidateToken()
    {
        // Register random user
        list ($email, $password, $statusCode, $response ) = $this->registerRandomUser();

        $this->assertSame(200, $statusCode, "Unable to register user");

        // Auth new USer
        list ($statusCode, $responseContents) = $this->authUser($email, $password);

        $this->assertSame(
            200,
            $statusCode,
            "Auth User Failed"
        );
        $responseArray = json_decode($responseContents, true);
        $jwt = $responseArray['data']['jwt'];

        $validateResponse = $this->client->post(self::API_URL . "?rest_route=/simple-jwt-login/v1/auth/validate", [
            'body' => json_encode([
                'JWT' => $jwt
            ]),
        ]);

        $this->assertSame(200, $validateResponse->getStatusCode(), "unable to validate token");
        $validateRespArr = json_decode($validateResponse->getBody()->getContents(), true);

        $this->assertTrue($validateRespArr['success']);
        $this->assertArrayHasKey('data', $validateRespArr);
        $this->assertArrayHasKey('user', $validateRespArr['data']);
        $this->assertArrayHasKey('jwt', $validateRespArr['data']);
        $this->assertArrayHasKey('roles', $validateRespArr['data']);
        $this->assertSame($email, $validateRespArr['data']['user']['user_email']);

        // Cleanup
        list($statusCode, $response) = $this->deleteUser($jwt);
        $this->assertSame(200, $statusCode, "unable to delete the user");
    }
}
