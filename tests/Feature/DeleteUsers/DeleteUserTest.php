<?php

namespace SimpleJwtLoginTests\Feature\DeleteUsers;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use SimpleJWTLogin\ErrorCodes;
use SimpleJwtLoginTests\Feature\TestBase;

class DeleteUserTest extends TestBase
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
    public static function deleteUserValidationProvider()
    {
        return [
            'empty request' => [
                'queryParams' => '',
                'options' => [],
                'errorMessage' => 'The `jwt` parameter is missing.',
                'errorCode' => ErrorCodes::ERR_DELETE_MISSING_JWT,
            ],
            'empty jwt in query params' => [
                'queryParams' => 'JWT=',
                'options' => [],
                'errorMessage' => 'The `jwt` parameter is missing.',
                'errorCode' => ErrorCodes::ERR_DELETE_MISSING_JWT,
            ],
            'invalid JWT in query params' => [
                'queryParams' => 'JWT=123.123.123',
                'options' => [],
                'errorMessage' => 'Malformed UTF-8 characters',
                'errorCode' => ErrorCodes::ERR_UNKNOWN_ERROR,
            ],
            'empty jwt in headers' => [
                'queryParams' => '',
                'options' => [
                    'headers' => [
                        'JWT' => ''
                    ]
                ],
                'errorMessage' => 'The `jwt` parameter is missing.',
                'errorCode' => ErrorCodes::ERR_DELETE_MISSING_JWT,
            ],
            'invalid JWT in headers' => [
                'queryParams' => '',
                'options' => [
                    'headers' => [
                        'Authorization' => '123.123.123'
                    ]
                ],
                'errorMessage' => 'Malformed UTF-8 characters',
                'errorCode' => ErrorCodes::ERR_UNKNOWN_ERROR,
            ],
        ];
    }

    #[DataProvider('deleteUserValidationProvider')]
    #[TestDox("Delete User Validations")]
    /**
     * @param ?string $queryParams
     * @param array $options
     * @param string $errorMessage
     * @param int $errorCode
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testValidateDeleteUser($queryParams, $options, $errorMessage, $errorCode)
    {
        $response = $this->client->delete(
            self::API_URL . "?rest_route=/simple-jwt-login/v1/users&" . $queryParams,
            $options,
        );

        $contents = $response->getBody()->getContents();
        $contentsArr = json_decode($contents, true);

        $expectedError = $this->generateErrorJson(
            $errorMessage,
            $errorCode
        );

        $this->assertSame(
            $expectedError,
            $contentsArr
        );
    }

    #[TestDox("Delete User by passing the JWT in headers")]
    public function testDeleteUserUsingHeaders()
    {
        // Register random user
        list ($email, $password, $statusCode ) = $this->registerRandomUser();
        $this->assertSame(200, $statusCode, "Unable to register user");

        $jwt = $this->getJWTForUser($email, $password);

        // Delete the user
        $result = $this->client->delete(
            self::API_URL . "?rest_route=/simple-jwt-login/v1/users",
            [
                'headers' => [
                    'Authorization' => $jwt
                ]
            ],
        );
        $this->assertSame(200, $result->getStatusCode());

        // Make sure the user does not exist anymore
        list ($statusCode) = $this->authUser($email, $password);
        $this->assertSame(400, $statusCode);
    }

    #[TestDox("Delete User by passing the JWT in query params")]
    public function testDeleteUserUsingQueryParams()
    {
        // Register random user
        list ($email, $password, $statusCode ) = $this->registerRandomUser();
        $this->assertSame(200, $statusCode, "Unable to register user");

        $jwt = $this->getJWTForUser($email, $password);

        // Delete the user
        $result = $this->client->delete(
            self::API_URL . "?rest_route=/simple-jwt-login/v1/users&JWT=" . $jwt,
        );
        $this->assertSame(200, $result->getStatusCode());

        // Make sure the user does not exist anymore
        list ($statusCode) = $this->authUser($email, $password);
        $this->assertSame(400, $statusCode);
    }
}
