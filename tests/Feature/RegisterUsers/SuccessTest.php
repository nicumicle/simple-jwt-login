<?php

namespace SimpleJwtLoginTests\Feature\RegisterUsers;

use Faker\Factory;
use PHPUnit\Framework\Attributes\TestDox;
use SimpleJwtLoginTests\Feature\TestBase;

class SuccessTest extends TestBase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
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
            "allowed_user_meta" => "first_name, last_name",
        ]);
    }

    #[TestDox("User can register with query params")]
    public function testSuccessWithQueryParams()
    {
        $faker = Factory::create();
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

    #[TestDox("User can register with form data")]
    public function testSuccessWithFormData()
    {
        $faker = Factory::create();
        $uri = self::API_URL . "?rest_route=/simple-jwt-login/v1/users";
        $result = $this->client->post($uri, [
            'form_params' => [
                "email"  => $faker->numberBetween(0, 1000) . $faker->email(),
                "password" => 123123,
            ]
        ]);

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

    #[TestDox("User can register with JSON body")]
    public function testSuccessWithJSONBody()
    {
        $faker = Factory::create();
        $uri = self::API_URL . "?rest_route=/simple-jwt-login/v1/users";
        $result = $this->client->post($uri, [
            'body' => json_encode([
                "email"  => $faker->numberBetween(0, 1000) . $faker->email(),
                "password" => 123123,
            ]),
        ]);

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

    #[TestDox("User can register with custom user_meta")]
    /**
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testSuccessWithUserMeta()
    {
        $faker = Factory::create();
        $firstName = $faker->firstNameMale();
        $lastName = $faker->lastName();

        $uri = self::API_URL . "?rest_route=/simple-jwt-login/v1/users";
        $result = $this->client->post($uri, [
            'body' => json_encode([
                "email"  => $faker->numberBetween(0, 1000) . $faker->email(),
                "password" => 123123,
                "user_meta" => [
                    "first_name" => $firstName,
                    "last_name" => $lastName,
                ],
            ]),
        ]);

        $this->assertSame(
            200,
            $result->getStatusCode()
        );

        $contents = $result->getBody()->getContents();
        $this->assertJson($contents);
        $json = json_decode($contents, true);
        $this->assertArrayHasKey('success', $json);
        $this->assertSame(true, $json['success']);
        $this->assertArrayHasKey('user', $json);
        $this->assertArrayHasKey('ID', $json['user']);

        // Test user meta registered
         $userMeta = $this->getUserMeta($json['user']['ID']);
         $this->assertNotEmpty($userMeta);
         $this->assertSame($firstName, $userMeta['first_name']);
         $this->assertSame($lastName, $userMeta['last_name']);
    }
}
