<?php

namespace SimpleJwtLoginTests\Feature\RegisterUsers;

use SimpleJWTLogin\ErrorCodes;
use SimpleJwtLoginTests\Feature\TestBase;

class RegisterUserValidationsTest extends TestBase
{
    public function setUp(): void
    {
        parent::setUp();
        self::updateOption([
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
                'expected_error' => self::generateErrorJson(
                    'Missing email or password.',
                    ErrorCodes::ERR_REGISTER_MISSING_EMAIL_OR_PASSWORD
                )
            ],
            'empty_strings' => [
                'email'  => "",
                'username' => "",
                'password' => "",
                'expected_error' => self::generateErrorJson(
                    'Missing email or password.',
                    ErrorCodes::ERR_REGISTER_MISSING_EMAIL_OR_PASSWORD
                )
            ],
            'invalid_email_address' => [
                'email'  => "abc",
                'username' => null,
                'password' => "123",
                'expected_error' => self::generateErrorJson(
                    'Invalid email address.',
                    ErrorCodes::ERR_REGISTER_INVALID_EMAIL_ADDRESS
                )
            ],
            'valid_email_and_no_password' => [
                'email'  => "test@simplejwtlogin",
                'username' => null,
                'password' => null,
                'expected_error' => self::generateErrorJson(
                    'Missing email or password.',
                    ErrorCodes::ERR_REGISTER_MISSING_EMAIL_OR_PASSWORD
                )
            ],
            'valid_username_and_no_password' => [
                'email'  => null,
                'username' => "admin",
                'password' => null,
                'expected_error' => self::generateErrorJson(
                    'Missing email or password.',
                    ErrorCodes::ERR_REGISTER_MISSING_EMAIL_OR_PASSWORD
                )
            ],

        ];
    }

    /**
     * @testdox Register User Validation errors with query params
     * @dataProvider registerProvider
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testRegisterValidationErrors($email, $username, $password, $expectedError)
    {
        $uri = self::API_URL . "?rest_route=/simple-jwt-login/v1/users";
        $result = $this->client->post($uri . $this->initParams('query', $email, $username, $password));
        $this->assertSame(
            400,
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

    /**
     * @testdox Register User Validation errors with JSON body
     * @dataProvider registerProvider
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testRegisterValidationErrorsBodyJSON($email, $username, $password, $expectedError)
    {
        $uri = self::API_URL . "?rest_route=/simple-jwt-login/v1/users";
        $result = $this->client->post($uri, [
            'body' => json_encode($this->initParams('body', $email, $username, $password)),
        ]);
        $this->assertSame(
            400,
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

    /**
     * @testdox Register User Validation errors with form params
     * @dataProvider registerProvider
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testRegisterValidationErrorsBodyFormParams($email, $username, $password, $expectedError)
    {
        $uri = self::API_URL . "?rest_route=/simple-jwt-login/v1/users";
        $result = $this->client->post($uri, [
            'form_params' => $this->initParams('form_params', $email, $username, $password),
        ]);
        $this->assertSame(
            400,
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

        throw new \Exception("invalid type " . $requestType);
    }
}
