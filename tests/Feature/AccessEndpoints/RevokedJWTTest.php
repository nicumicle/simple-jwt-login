<?php

namespace SimpleJwtLoginTests\Feature\AccessEndpoints;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use SimpleJWTLogin\ErrorCodes;
use SimpleJwtLoginTests\Feature\TestBase;

class RevokedJWTTest extends TestBase
{
    /**
     * @var string $decryptionKey
     */
    private static $decryptionKey = "test";
    
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
            "decryption_key" => self::$decryptionKey,
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
            "allow_autologin" => true,
            // Reset password
            'allow_reset_password' => true,
            'reset_password_jwt' => true,
            // API Middleware
            "api_middleware" => [
                "enabled" =>  true,
            ],
            // Protect endpoints
            "protect_endpoints" => [
                "enabled" => 1,
                "action" => 2,
                "protect" => [
                    "/wp/v2/users",
                ],
                "whitelist" => [],
            ],
        ]);
    }

    /**
     * @return array[]
     */
    public static function endpointsProvider()
    {
        return [
            'autologin' => [
                'method' => 'GET',
                'endpoint' => '/simple-jwt-login/v1/autologin',
            ],
            'delete_user' => [
                'method' => 'DELETE',
                'endpoint' => '/simple-jwt-login/v1/users',
            ],
            'change_password' => [
                'method' => 'PUT',
                'endpoint' => '/simple-jwt-login/v1/user/reset_password&new_password=123',
            ],
            'auth_refresh' => [
                'method' => 'POST',
                'endpoint' => '/simple-jwt-login/v1/auth/refresh',
            ],
            'auth_validate' => [
                'method' => 'POST',
                'endpoint' => '/simple-jwt-login/v1/auth/validate',
            ],
            'auth_validate_get' => [
                'method' => 'GET',
                'endpoint' => '/simple-jwt-login/v1/auth/validate',
            ],
            'get_posts' => [
                'method' => 'GET',
                'endpoint' => '/wp/v2/posts',
            ],
            'get_protected_endpoint_wp_users' => [
                'method' => 'GET',
                'endpoint' => '/wp/v2/users',
            ],
        ];
    }

    #[DataProvider('endpointsProvider')]
    #[TestDox("Calling endpoints with revoked JWT")]
    /**
     * @param string $method
     * @param string $endpoint
     * @return void
     */
    public function testRevokedJWT($method, $endpoint)
    {
        $this->initClient(
            [
                'cookies' => true,
                'http_errors' => false,
                'allow_redirects' => [
                    'track_redirects' => true,
                ],
            ]
        );

        // Register random user
        list ($email, $password, $statusCode, $response) = $this->registerRandomUser();

        $this->assertSame(200, $statusCode, "Unable to register user");

        // Auth new User
        list ($statusCode, $responseContents) = $this->authUser($email, $password);

        $this->assertSame(
            200,
            $statusCode,
            "Revoked JWT failed to authenticate user"
        );

        $authResponse = json_decode($responseContents, true);
        $this->assertNotNull($authResponse, "unable to decode response");
        $this->assertArrayHasKey('data', $authResponse);
        $this->assertArrayHasKey('jwt', $authResponse['data']);
        $jwt = $authResponse['data']['jwt'];

        // Revoke the token
        $revokeResp = $this->client->post(
            self::API_URL . "/?rest_route=/simple-jwt-login/v1/auth/revoke",
            [
                'body' => json_encode([
                    'jwt' => $jwt,
                ]),
                'headers' => [
                    'Content-type' => 'application/json',
                ]
            ]
        );
        $contents = $revokeResp->getBody()->getContents();
        $contentsArr = json_decode($contents, true);
        $this->assertArrayHasKey('success', $contentsArr);
        $this->assertTrue($contentsArr['success']);

        $response = $this->client->request(
            $method,
            self::API_URL . "/?rest_route=" . $endpoint,
            [
                'body' => json_encode([
                    'jwt' => $jwt,
                    'email' => $email,
                    'password' => $password,
                ]),
                'headers' => [
                    'Content-type' => 'application/json',
                ]
            ]
        );

        $contents = $response->getBody()->getContents();
        $contentsArr = json_decode($contents, true);

        $this->assertArrayHasKey('success', $contentsArr);
        $this->assertFalse($contentsArr['success']);
        $this->assertArrayHasKey('data', $contentsArr);
        $this->assertArrayHasKey('message', $contentsArr['data']);
        $this->assertEquals("This JWT is invalid.", $contentsArr['data']['message']);
    }
}
