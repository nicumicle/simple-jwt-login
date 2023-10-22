<?php

namespace SimpleJwtLoginTests\Feature\TestAccessEndpoints;

use Faker\Factory;
use SimpleJWTLogin\ErrorCodes;
use SimpleJwtLoginTests\Feature\TestBase;

class RegisterUserTest extends TestBase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->initClient();
    }

    public function testEmptyOptions()
    {
        $this->updateOption([]);

        $result = $this->client->post(self::API_URL . "?rest_route=/simple-jwt-login/v1/users");
        $this->assertSame(
            400,
            $result->getStatusCode()
        );

        $contents = $result->getBody()->getContents();
        $this->assertJson($contents);
        $json = json_decode($contents, true);
        $this->assertEquals(
            $json,
            self::generateErrorJson("Register is not allowed.", ErrorCodes::ERR_REGISTER_IS_NOT_ALLOWED)
        );
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

        ];
    }
    /**
     * @dataProvider registerProvider
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testRegisterValidationErrors($email, $username, $password, $expectedError)
    {
        $this->updateOption([
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

        $uri = self::API_URL . "?rest_route=/simple-jwt-login/v1/users";
        if ($username != null) {
            $uri .= "&username=$username";
        }
        if ($email != null) {
            $uri .= "&email=$email";
        }
        if ($password != null) {
            $uri .= "&password=$password";
        }

        $result = $this->client->post($uri);
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

    public function testRegisterSuccess()
    {
        $faker = Factory::create();

        $this->updateOption([
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

        $uri = self::API_URL . "?rest_route=/simple-jwt-login/v1/users";
        $uri .= "&email=" . $faker->numberBetween(0, 1000) . $faker->email();
        $uri .= "&password=123123";
        $result = $this->client->post($uri);

        $this->assertSame(
            200,
            $result->getStatusCode()
        );

        $contents = $result->getBody()->getContents();
        $this->assertJson($contents);
        $json = json_decode($contents, true);
        $this->assertArrayHasKey('success', $json);
        $this->assertSame(true, $json['success']);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->updateOption($this->initialOption); //Update initial option
    }
}
