<?php

namespace SimpleJwtLoginTests\Feature\RegisterUsers;

use Faker\Factory;
use PHPUnit\Framework\Attributes\TestDox;
use SimpleJwtLoginTests\Feature\FeatureTestCase;

class MultipleRolesTest extends FeatureTestCase
{
    private const ROUTE = '/simple-jwt-login/v1/users';

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::updateSimpleJWTOption([
            'allow_register'         => true,
            'new_user_profile'       => 'subscriber, editor',
            'register_ip'            => '',
            'register_domain'        => '',
            'require_register_auth'  => false,
            'random_password'        => false,
            'random_password_length' => 10,
            'register_force_login'   => false,
            'register_jwt'           => false,
            'allowed_user_meta'      => '',
        ]);
    }

    #[TestDox('Registered user receives all comma-separated default roles')]
    public function testRegisterAssignsMultipleRoles(): void
    {
        $faker    = Factory::create();
        $response = $this->jsonRequest('POST', self::ROUTE, [
            'email'    => $faker->randomNumber(6) . $faker->email(),
            'password' => 'password123',
        ]);

        $body = json_decode((string) $response->getBody(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($body['success']);
        $this->assertContains('subscriber', $body['data']['roles']);
        $this->assertContains('editor', $body['data']['roles']);
    }

    #[TestDox('All assigned roles are persisted in wp_usermeta capabilities')]
    public function testRolesPersistedInDatabase(): void
    {
        $faker    = Factory::create();
        $email    = $faker->randomNumber(6) . $faker->email();
        $response = $this->jsonRequest('POST', self::ROUTE, [
            'email'    => $email,
            'password' => 'password123',
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $userId = json_decode((string) $response->getBody(), true)['data']['id'];

        $meta         = $this->getUserMeta($userId);
        $capsKey      = self::getTablePrefix() . 'capabilities';
        $capabilities = isset($meta[$capsKey]) ? unserialize($meta[$capsKey]) : [];

        $this->assertArrayHasKey('subscriber', $capabilities);
        $this->assertArrayHasKey('editor', $capabilities);
    }

    #[TestDox('Whitespace around role names is trimmed before assignment')]
    public function testRolesAreTrimmed(): void
    {
        self::updateSimpleJWTOption([
            'allow_register'         => true,
            'new_user_profile'       => '  subscriber  ,  contributor  ',
            'register_ip'            => '',
            'register_domain'        => '',
            'require_register_auth'  => false,
            'random_password'        => false,
            'random_password_length' => 10,
            'register_force_login'   => false,
            'register_jwt'           => false,
            'allowed_user_meta'      => '',
        ]);

        $faker    = Factory::create();
        $response = $this->jsonRequest('POST', self::ROUTE, [
            'email'    => $faker->randomNumber(6) . $faker->email(),
            'password' => 'password123',
        ]);

        $body = json_decode((string) $response->getBody(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertContains('subscriber', $body['data']['roles']);
        $this->assertContains('contributor', $body['data']['roles']);
    }
}
