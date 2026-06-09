<?php

namespace SimpleJwtLoginTests\Feature\RegisterUsers;

use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use SimpleJWTLogin\ErrorCodes;
use SimpleJwtLoginTests\Feature\TestBase;

class ValidationsTest extends TestBase
{
    public function setUp(): void
    {
        parent::setUp();
        self::updateSimpleJWTOption([
            "allow_register" => true,
            "new_user_profile" => "subscriber",
            "register_ip" => "",
            "register_domain" => "",
            "require_register_auth" => false,
            "random_password" => false,
            "random_password_length" => 10,
            "register_force_login" => false,
            "register_jwt" => false,
            "allowed_user_meta" => "",
        ]);
    }

    public static function registerProvider(): array
    {
        return [
            'empty_credentials' => [
                'email'  => null,
                'username' => null,
                'password' => null,
                'expectedStatusCode' => 422,
                'expectedError' => self::generateErrorJson(
                    'Missing email.',
                    ErrorCodes::ERR_REGISTER_MISSING_EMAIL_OR_PASSWORD
                )
            ],
            'empty_strings' => [
                'email'  => "",
                'username' => "",
                'password' => "",
                'expectedStatusCode' => 422,
                'expectedError' => self::generateErrorJson(
                    'Missing email.',
                    ErrorCodes::ERR_REGISTER_MISSING_EMAIL_OR_PASSWORD
                )
            ],
            'invalid_email_address' => [
                'email'  => "abc",
                'username' => null,
                'password' => "123",
                'expectedStatusCode' => 422,
                'expectedError' => self::generateErrorJson(
                    'Invalid email address.',
                    ErrorCodes::ERR_REGISTER_INVALID_EMAIL_ADDRESS
                )
            ],
            'valid_email_and_no_password' => [
                'email'  => "test@simplejwtlogin",
                'username' => null,
                'password' => null,
                'expectedStatusCode' => 422,
                'expectedError' => self::generateErrorJson(
                    'Missing password.',
                    ErrorCodes::ERR_REGISTER_MISSING_EMAIL_OR_PASSWORD
                )
            ],
            'valid_username_and_no_password' => [
                'email'  => null,
                'username' => "admin",
                'password' => null,
                'expectedStatusCode' => 422,
                'expectedError' => self::generateErrorJson(
                    'Missing email.',
                    ErrorCodes::ERR_REGISTER_MISSING_EMAIL_OR_PASSWORD
                )
            ],

        ];
    }

    #[DataProvider('registerProvider')]
    #[TestDox("Register User Validation errors with query params")]
    /**
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testRegisterValidationErrors($email, $username, $password, $expectedStatusCode, $expectedError)
    {
        $uri = self::API_URL . "?rest_route=/simple-jwt-login/v1/users";
        $result = $this->client->post($uri . $this->initParams('query', $email, $username, $password));
        $this->assertSame(
            $expectedStatusCode,
            $result->getStatusCode()
        );

        $contents = $result->getBody()->getContents();
        $this->assertJson($contents);
        $json = json_decode($contents, true);
        $this->assertEquals(
            $json,
            $expectedError
        );
    }

    #[DataProvider('registerProvider')]
    #[TestDox("Register User Validation errors with JSON body")]
    /**
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testRegisterValidationErrorsBodyJSON($email, $username, $password, $expectedStatusCode, $expectedError)
    {
        $uri = self::API_URL . "?rest_route=/simple-jwt-login/v1/users";
        $result = $this->client->post($uri, [
            'body' => json_encode($this->initParams('body', $email, $username, $password)),
        ]);
        $this->assertSame(
            $expectedStatusCode,
            $result->getStatusCode()
        );

        $contents = $result->getBody()->getContents();
        $this->assertJson($contents);
        $json = json_decode($contents, true);
        $this->assertEquals(
            $json,
            $expectedError
        );
    }

    #[DataProvider('registerProvider')]
    #[TestDox("Register User Validation errors with form params")]
    /**
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testRegisterValidationErrorsBodyFormParams($email, $username, $password, $expectedStatusCode, $expectedError)
    {
        $uri = self::API_URL . "?rest_route=/simple-jwt-login/v1/users";
        $result = $this->client->post($uri, [
            'form_params' => $this->initParams('form_params', $email, $username, $password),
        ]);
        $this->assertSame(
            $expectedStatusCode,
            $result->getStatusCode()
        );

        $contents = $result->getBody()->getContents();
        $this->assertJson($contents);
        $json = json_decode($contents, true);
        $this->assertEquals(
            $json,
            $expectedError
        );
    }

    #[TestDox("Registering a duplicate email returns 409")]
    public function testRegisterDuplicateEmailReturns409(): void
    {
        $email = 'dup_' . uniqid() . '@example.com';
        $uri   = self::API_URL . '?rest_route=/simple-jwt-login/v1/users';

        $first = $this->client->post($uri, [
            'body' => json_encode(['email' => $email, 'password' => '1234']),
        ]);
        $this->assertSame(200, $first->getStatusCode(), 'first registration failed');

        $second = $this->client->post($uri, [
            'body' => json_encode(['email' => $email, 'password' => '1234']),
        ]);
        $this->assertSame(409, $second->getStatusCode());
        $body = json_decode($second->getBody()->getContents(), true);
        $this->assertFalse($body['success']);
        $this->assertSame(ErrorCodes::ERR_REGISTER_USER_ALREADY_EXISTS, $body['data']['error_code']);
    }

    #[TestDox("Registering a duplicate username returns 409")]
    public function testRegisterDuplicateUsernameReturns409(): void
    {
        $login = 'dupuser_' . uniqid();
        $uri   = self::API_URL . '?rest_route=/simple-jwt-login/v1/users';

        $first = $this->client->post($uri, [
            'body' => json_encode([
                'email'      => $login . '@example.com',
                'user_login' => $login,
                'password'   => '1234',
            ]),
        ]);
        $this->assertSame(200, $first->getStatusCode(), 'first registration failed');

        $second = $this->client->post($uri, [
            'body' => json_encode([
                'email'      => 'other_' . $login . '@example.com',
                'user_login' => $login,
                'password'   => '1234',
            ]),
        ]);
        $this->assertSame(409, $second->getStatusCode());
        $body = json_decode($second->getBody()->getContents(), true);
        $this->assertFalse($body['success']);
        $this->assertSame(ErrorCodes::ERR_REGISTER_USER_ALREADY_EXISTS, $body['data']['error_code']);
    }

    /**
     * @param string $requestType
     * @param ?string $email
     * @param ?string $username
     * @param ?string $password
     * @return array|string
     * @throws \Exception
     */
    private function initParams($requestType, $email, $username, $password)
    {
        switch ($requestType) {
            case "query":
                $uri = "";
                if ($username != null) {
                    $uri .= "&username=$username";
                }
                if ($email != null) {
                    $uri .= "&email=$email";
                }
                if ($password != null) {
                    $uri .= "&password=$password";
                }

                return $uri;
            case "body":
            case "form_params":
                $body = [];
                if ($username != null) {
                    $body['username'] = $username;
                }
                if ($email != null) {
                    $body['email'] = $email;
                }
                if ($password != null) {
                    $body['password'] = $password;
                }

                return $body;
        }

        throw new Exception("invalid type " . $requestType);
    }
}
