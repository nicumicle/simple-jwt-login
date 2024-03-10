<?php

declare(strict_types=1);

namespace SimpleJwtLoginTests\Feature\ProtectEndpoints;

use PHPUnit\Framework\Attributes\TestDox;
use SimpleJwtLoginTests\Feature\TestBase;

class NotActiveTest extends TestBase
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
                "enabled" => false,
                "action" => 2,
                "protect" => [],
                "whitelist" => [],
            ],
        ]);
    }


    #[TestDox("WordPress endpoint can be accesses if protect endpoints is disabled")]
    /**
     * @return void
     */
    public function testCanAccessEndpoint()
    {
        $resp = $this->client->get(self::API_URL . "?rest_route=/wp/v2/users");

        $this->assertEquals(200, $resp->getStatusCode());
    }
}
