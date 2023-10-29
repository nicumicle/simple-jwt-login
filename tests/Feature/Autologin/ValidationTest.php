<?php

namespace SimpleJwtLoginTests\Feature\Autologin;

use SimpleJWTLogin\ErrorCodes;
use SimpleJwtLoginTests\Feature\TestBase;

class ValidationTest extends TestBase
{
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
            "decryption_key" => "test",
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
        ]);
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    public static function autologinValidationProvider()
    {
        return [
            'empty_jwt' => [
                'jwt' => null,
                'error_message' => 'Wrong Request.',
                'error_code' => ErrorCodes::ERR_VALIDATE_LOGIN_WRONG_REQUEST,
            ],
            'invalid_jwt' => [
                'jwt' => "123",
                'error_message' => 'Wrong number of segments',
                'error_code' => ErrorCodes::ERR_WRONG_NUMBER_OF_SEGMENTS,
            ],
            'invalid_jwt_values' => [
                'jwt' => "1.1.2",
                'error_message' => 'Syntax error, malformed JSON',
                'error_code' => ErrorCodes::ERR_UNKNOWN_ERROR,
            ],
        ];
    }

    /**
     * @testdox Autologin Validation with JWT as Query Parameter
     * @dataProvider autologinValidationProvider
     * @param ?string $jwt
     * @param string $expectedErrorMessage
     * @param int $expectedErrorCode
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testJWTInQueryParams($jwt, $expectedErrorMessage, $expectedErrorCode)
    {
        $response = $this->client->get(
            self::API_URL . "?rest_route=/simple-jwt-login/v1/autologin&JWT=" . $jwt,
        );

        $contents = $response->getBody()->getContents();
        $contentsArr = json_decode($contents, true);

        $expectedError = $this->generateErrorJson(
            $expectedErrorMessage,
            $expectedErrorCode
        );

        $this->assertSame(
            $expectedError,
            $contentsArr,
        );
    }

    /**
     * @testdox Autologin Validation with JWT in the Header
     * @dataProvider autologinValidationProvider
     * @param ?string $jwt
     * @param string $expectedErrorMessage
     * @param int $expectedErrorCode
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testJWTInHeader($jwt, $expectedErrorMessage, $expectedErrorCode)
    {
        $response = $this->client->get(
            self::API_URL . "?rest_route=/simple-jwt-login/v1/autologin",
            [
                'headers' => [
                    'Authorization' => $jwt,
                ],
            ],
        );

        $contents = $response->getBody()->getContents();
        $contentsArr = json_decode($contents, true);

        $expectedError = $this->generateErrorJson(
            $expectedErrorMessage,
            $expectedErrorCode
        );

        $this->assertSame(
            $expectedError,
            $contentsArr,
        );
    }
}
