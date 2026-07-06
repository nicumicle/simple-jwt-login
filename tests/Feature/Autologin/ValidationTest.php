<?php

namespace SimpleJwtLoginTests\Feature\Autologin;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use SimpleJWTLogin\ErrorCodes;
use SimpleJwtLoginTests\Feature\TestBase;

class ValidationTest extends TestBase
{
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
            'decryption_key'          => 'test',
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
            'allow_autologin'         => true,
        ];
    }

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::updateSimpleJWTOption(self::baseSettings());
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    public static function autologinValidationProvider()
    {
        return [
            'empty_jwt' => [
                'jwt' => null,
                'errorMessage' => 'JWT is missing.',
                'errorCode' => ErrorCodes::ERR_JWT_IS_MISSING,
            ],
            'invalid_jwt' => [
                'jwt' => "123",
                'errorMessage' => 'Wrong number of segments',
                'errorCode' => ErrorCodes::ERR_WRONG_NUMBER_OF_SEGMENTS,
            ],
            'invalid_jwt_values' => [
                'jwt' => "1.1.2",
                'errorMessage' => 'Syntax error, malformed JSON',
                'errorCode' => ErrorCodes::ERR_UNKNOWN_ERROR,
            ],
        ];
    }

    #[DataProvider('autologinValidationProvider')]
    #[TestDox("Autologin Validation with JWT as Query Parameter")]
    /**
     * @param ?string $jwt
     * @param string $errorMessage
     * @param int $errorCode
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testJWTInQueryParams($jwt, $errorMessage, $errorCode)
    {
        $response = $this->client->get(
            self::API_URL . "?rest_route=/simple-jwt-login/v1/autologin&JWT=" . $jwt
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

    #[DataProvider('autologinValidationProvider')]
    #[TestDox("Autologin Validation with JWT in the Header")]
    /**
     * @param ?string $jwt
     * @param string $errorMessage
     * @param int $errorCode
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testJWTInHeader($jwt, $errorMessage, $errorCode)
    {
        $response = $this->client->get(
            self::API_URL . "?rest_route=/simple-jwt-login/v1/autologin",
            [
                'headers' => [
                    'Authorization' => $jwt,
                ],
            ]
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

    #[TestDox("Autologin: bare JWT in Authorization header is ignored when Bearer prefix is required")]
    public function testBearerRequiredRejectsBareJwtInHeader(): void
    {
        list ($email, $password, $statusCode) = $this->registerRandomUser();
        $this->assertSame(200, $statusCode, 'register failed');
        $jwt = $this->getJWTForUser($email, $password);

        self::updateSimpleJWTOption(array_merge(self::baseSettings(), [
            'request_jwt_header_require_bearer' => true,
        ]));
        try {
            $response = $this->client->get(
                self::API_URL . '?rest_route=/simple-jwt-login/v1/autologin',
                ['headers' => ['Authorization' => $jwt]]
            );

            $body = json_decode($response->getBody()->getContents(), true);
            $this->assertFalse($body['success']);
            $this->assertSame(ErrorCodes::ERR_JWT_IS_MISSING, $body['data']['error_code']);
        } finally {
            self::updateSimpleJWTOption(self::baseSettings());
            $this->deleteUser($jwt);
        }
    }

    #[TestDox("Autologin: Bearer-prefixed JWT in header is processed when Bearer prefix is required")]
    public function testBearerRequiredAcceptsBearerJwtInHeader(): void
    {
        self::updateSimpleJWTOption(array_merge(self::baseSettings(), [
            'request_jwt_header_require_bearer' => true,
        ]));

        list ($email, $password, $statusCode) = $this->registerRandomUser();
        $this->assertSame(200, $statusCode, 'register failed');
        $jwt = $this->getJWTForUser($email, $password);

        try {
            $response = $this->client->get(
                self::API_URL . '?rest_route=/simple-jwt-login/v1/autologin',
                [
                    'headers'         => ['Authorization' => 'Bearer ' . $jwt],
                    'allow_redirects' => false,
                ]
            );

            // Successful autologin redirects to wp-admin; a 302 confirms the JWT was found and accepted.
            $this->assertSame(302, $response->getStatusCode());
            $location = $response->getHeaderLine('Location');
            $this->assertStringContainsString('wp-admin', $location);
        } finally {
            self::updateSimpleJWTOption(self::baseSettings());
            $this->deleteUser($jwt);
        }
    }
}
