<?php

declare(strict_types=1);

namespace SimpleJwtLoginTests\Feature\ProtectEndpoints;

use SimpleJWTLogin\Modules\Settings\ProtectEndpointSettings;
use SimpleJwtLoginTests\Feature\TestBase;

class ActiveOnSpecificEndpointsTest extends TestBase
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
            // Protect endpoints
            'protect_endpoints' => [
                "enabled" => true,
                "action" => ProtectEndpointSettings::SPECIFIC_ENDPOINTS,
                "protect" => [
                    "/wp/v2/users"
                ],
                "whitelist" => [
                ],
            ],
        ]);
    }

    /**
     * @testdox WordPress endpoint can be accessed without JWT if not protected
     * @return void
     */
    public function testCanAccessNotProtectedEndpoint()
    {
        $resp = $this->client->get(self::API_URL . "?rest_route=/wp/v2/posts");

        $this->assertEquals(200, $resp->getStatusCode());
    }

    /**
     * @testdox WordPress endpoint can't be accessed without JWT if protected
     * @return void
     */
    public function testEndpointCanNotBeAccessedWithoutJWT()
    {
        $resp = $this->client->get(self::API_URL . "?rest_route=/wp/v2/users");

        $this->assertEquals(403, $resp->getStatusCode());
        $contents = $resp->getBody()->getContents();
        $contentsArr = json_decode($contents, true);
        $this->assertSame(
            $this->generateErrorJson(
                "You are not authorized to access this endpoint.",
                403,
                ['type' => 'simple-jwt-login-route-protect'],
            ),
            $contentsArr,
        );
    }

    /**
     * @testdox WordPress endpoint can be accessed with JWT if whitelisted
     * @return void
     */
    public function testEndpointCanBeAccessedWithJWT()
    {
        // Register random user
        list ($email, $password, $statusCode) = $this->registerRandomUser();

        $this->assertSame(200, $statusCode, "Unable to register user");

        // Auth new USer
        list ($statusCode, $responseContents) = $this->authUser($email, $password);

        $this->assertSame(
            200,
            $statusCode,
            "Autologin failed to authenticate user "
        );

        $authResponse = json_decode($responseContents, true);
        $this->assertArrayHasKey('data', $authResponse);
        $this->assertArrayHasKey('jwt', $authResponse['data']);
        $jwt = $authResponse['data']['jwt'];


        $resp = $this->client->get(self::API_URL . "?rest_route=/wp/v2/users&jwt=" . $jwt);

        $this->assertEquals(200, $resp->getStatusCode());
    }
}
