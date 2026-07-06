<?php

declare(strict_types=1);

namespace SimpleJwtLoginTests\Feature\ProtectEndpoints;

use PHPUnit\Framework\Attributes\TestDox;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Modules\Settings\ProtectEndpointSettings;
use SimpleJwtLoginTests\Feature\TestBase;

class RoleBasedRulesTest extends TestBase
{
    const JWT_SECRET_KEY = "123";

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::updateSimpleJWTOption([
            "allow_authentication" => true,
            "jwt_payload" => ["email", "exp", "id", "iss", "site", "username"],
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
            // Autologin: We need this for refresh token
            "jwt_login_by" => 0,
            "jwt_login_by_parameter" => "email",
            // Protect endpoints (new rules format)
            'protect_endpoints' => [
                "enabled" => true,
                "default_action" => ProtectEndpointSettings::DEFAULT_ALLOW_ALL,
                "rules_url" => [
                    "/wp/v2/users",
                    "/wp/v2/comments",
                ],
                "rules_method" => [
                    ProtectEndpointSettings::REQUEST_METHOD_ALL,
                    ProtectEndpointSettings::REQUEST_METHOD_ALL,
                ],
                "rules_match" => [
                    ProtectEndpointSettings::ENDPOINT_MATCH_START_WITH,
                    ProtectEndpointSettings::ENDPOINT_MATCH_START_WITH,
                ],
                "rules_type" => [
                    // "JWT required" rule that still carries a stale role from a
                    // previous "JWT + Roles" configuration.
                    ProtectEndpointSettings::RULE_TYPE_PROTECTED,
                    // "JWT + Roles" rule that must enforce the role.
                    ProtectEndpointSettings::RULE_TYPE_PROTECTED_ROLES,
                ],
                "rules_roles" => [
                    "administrator",
                    "administrator",
                ],
            ],
        ]);
    }

    #[TestDox("JWT required rule ignores the saved role: a subscriber with a valid JWT gets access")]
    /**
     * @return void
     */
    public function testJwtRequiredRuleIgnoresSavedRole()
    {
        list ($email, $password, $statusCode) = $this->registerRandomUser();
        $this->assertSame(200, $statusCode, "Unable to register user");

        // The rule protects the endpoint, so it is unreachable without a JWT.
        $noJwt = $this->client->get(self::API_URL . "?rest_route=/wp/v2/users");
        $this->assertEquals(401, $noJwt->getStatusCode(), "Endpoint should be protected");

        // The rule is "JWT required" but carries a stale "administrator" role.
        // A subscriber with a valid JWT must still be granted access.
        $jwt = $this->getJWTForUser($email, $password);
        $withJwt = $this->client->get(self::API_URL . "?rest_route=/wp/v2/users&jwt=" . $jwt);
        $this->assertEquals(
            200,
            $withJwt->getStatusCode(),
            "A JWT required rule must ignore the saved role and grant access to any authenticated user"
        );
    }

    #[TestDox("JWT + Roles rule enforces the role: a subscriber without the role is rejected")]
    /**
     * @return void
     */
    public function testJwtAndRolesRuleEnforcesRole()
    {
        list ($email, $password, $statusCode) = $this->registerRandomUser();
        $this->assertSame(200, $statusCode, "Unable to register user");

        // The rule protects the endpoint, so it is unreachable without a JWT.
        $noJwt = $this->client->get(self::API_URL . "?rest_route=/wp/v2/comments");
        $this->assertEquals(401, $noJwt->getStatusCode(), "Endpoint should be protected");

        // The rule is "JWT + Roles" requiring "administrator". A subscriber
        // with a valid JWT must be rejected because of the missing role.
        $jwt = $this->getJWTForUser($email, $password);
        $withJwt = $this->client->get(self::API_URL . "?rest_route=/wp/v2/comments&jwt=" . $jwt);

        $this->assertEquals(403, $withJwt->getStatusCode());
        $contentsArr = json_decode($withJwt->getBody()->getContents(), true);
        $this->assertSame(
            $this->generateErrorJson(
                "You do not have the required role to access this endpoint.",
                ErrorCodes::ERR_PROTECT_ENDPOINTS_INSUFFICIENT_ROLE
            ),
            $contentsArr
        );
    }
}
